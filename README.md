# The Digital Heir —  README

Prototype: https://thedigitalheir.com  
Repository: https://github.com/SKachurin/digital-inheritance

---

## 0) The problem this project solves

The core problem addressed by this project is how to safely use
**weak, human-memorable secrets** to protect **extremely sensitive information**
— without trusting any single system, company, or person.

Human secrets are inherently low-entropy and vulnerable to guessing.
At the same time, the information they protect (credentials, private keys,
instructions, personal data) may be critical and irreversible if leaked.

The Digital Heir approaches this problem using:
- a zero-knowledge design,
- strict separation of cryptographic responsibilities across independent systems,
- online brute-force resistance via rate-limited key unwrapping,
- and the elimination of single points of technical or coercive failure.

This architecture is then applied to a practical real-world scenario:
the controlled transfer of sensitive information when its owner becomes unavailable.

The Digital Heir lets a user create a single **Envelope** (an *encrypted note*) that is released to designated contacts if an inactivity-based **Pipeline** ends; the Envelope can only be unlocked by providing correct secret answers.

---

## 1) Terminology (consistent naming)

- **Envelope**: the encrypted note stored on our servers.
- **Answer**: user-provided secret that gates access to Envelope decryption.
- **Slot**: one answer “route” (up to 4 logical answers: A1, A2, B1, B2).
- **KMS**: Key Management System is an integrated solution for generating cryptographic keys. We are using 3 different KMS vendors for encrypt/decrypt each Envelope.
- **Replica**: one KMS instance/provider used to wrap the same DEK (up to 3 replicas per slot).
- **Triplet**: the 3 replicas (kms1/kms2/kms3) for a given slot.

---

## 2) Threat model & guarantees

### What we protect against
**If the database leaks:**
- All user fields are encrypted at rest (libsodium secretbox).
- Envelope data is stored as encrypted blobs; plaintext is not stored.
- Secret **answers are never stored** (not even hashed).

**If the app server is compromised (read access):**
- Encrypted-at-rest fields remain protected without the platform secrets.
- Envelope blobs remain protected without correct answers + KMS unwrap.

**Online brute-force attempts against answers:**
- Unwrap is rate-limited at the KMS Gateway layer (per: `user_id + answer_fp`).
- Additional attempt counters/lockouts are enforced at the Symfony app layer (per Envelope).

### What we do NOT protect against
- **Weak answers** (guessable secrets reduce security).
  - The system is designed around secrets that are easy for the intended recipient
    to remember, but hard for anyone else to guess.  
    The goal is not cryptographic strength of the answer itself, but *contextual exclusivity*.  
    For example: “The place where we spent our honeymoon.”  
    Such answers are:
    - trivial for the intended person,
    - meaningless to outsiders,
    - and protected against online guessing by strict rate limits and lockouts.
- **Compromised client device/browser** (keylogger/malware defeats client secrecy).
- **Compromised email/WhatsApp/Telegram account** (can impact Pipeline messaging).
- **User error**: if answers are forgotten, Envelope become permanently inaccessible.

---

## 3) Data stored & encryption at rest (server-side)

### General account/contact data encryption
General app data (contacts, names, phones, questions, etc.) is encrypted server-side at rest in DB using:

- **libsodium `crypto_secretbox` (XSalsa20-Poly1305)**

The symmetric key is derived in `CryptoService` from two environment secrets:
- `encryption_key` (env)
- `personal_string` (env)

Derivation (current design):
- Both are SHA-256’d
- Then XOR’d into a 32-byte key

### Which fields are plaintext
To operate the service, at least one identifier must remain readable:
- **Primary email** is stored in plaintext (used as login / routing / account lookup).
  Everything else is intended to be encrypted at rest.

---

## 4) Envelope cryptography (DEK + KMS wrapping)

Envelope encryption is **hybrid**:
- The note is encrypted under a random **DEK** (AES-256-GCM).
- The DEK is then wrapped using:
   - **Argon2id(answer → H)**, and
   - **KMS KEK** combined via **HKDF** to produce the wrapping key.

There can be:
- up to **4 slots** (answers)
- up to **3 KMS replicas** per slot  
  Max stored blobs: **4 × 3 = 12**.

### 4.1 Encrypt flow (creation)

#### Data Encryption Key (DEK) — 32 random bytes
- **Where**: browser (canonical design)
- **Why**: the only key that encrypts the note.

#### Note encryption
- Generate:
   - `DEK` (32B random)
   - `IV` (12B random)
- Compute:
   - `C = AES-256-GCM(DEK, Note, IV)` (includes tag)

#### For each provided answer (slot)
Let answer be `A`:
- Generate `Salt` (16B random)
- Compute:
   - `H = Argon2id(A, Salt, t=5, m=64MiB, p=1) → 32B`

Define fingerprint binding this slot to this ciphertext:
- `answer_fp = SHA-256( b64(C) || "." || b64(IV) || "." || b64(Salt) )`

#### KMS wrap (per replica)
For each replica KMS (kms1/kms2/kms3):

- KMS holds long-term secret `KEK` (never leaves KMS/HSM boundary conceptually)
- Derive wrapping key:
   - `KEK′ = HKDF-SHA256(KEK, salt=H, info="wrap-v2")`

Wrap DEK:
- `W = AES-256-GCM(KEK′, DEK, IV_wrap; AAD = user_id || answer_fp)`
- Stored format:
   - `W = IV_wrap || CT_wrap || TAG_wrap` (base64)

**Non-deterministic**: new `IV_wrap` each time.

### 4.2 What the DB stores (per slot per replica)

For each slot (answer) and each replica (kms1/kms2/kms3), the DB stores the JSON blob containing:

- `c` : base64 ciphertext of the note
- `iv`: base64 IV used for note encryption
- `w` : base64 wrapped DEK
- `s` : base64 Argon2 salt

That is the `{c, iv, w, s}` blob.  
Up to **12 blobs** total for one Envelope.

### 4.3 What is never stored
- Secret answers (A1/A2/B1/B2) are never stored.
- Derived `H` is never stored.
- The DEK is not stored in plaintext; it is stored only as wrapped `W`.

---

## 5) Decrypt flow (unlock)
Envelope decryption is a multi-stage process designed to prevent both
offline and online brute-force attacks, even when answers are low entropy.

The process intentionally separates responsibilities across independent systems.

### 5.1 Inputs
To attempt decryption, the user (or heir) provides:
- a candidate answer (human-memorable secret),
- the encrypted Envelope blobs `{c, iv, w, s}`,
- and the system context (`user_id`, slot metadata).

The answer itself is never stored.

### 5.2 Deriving the unwrap key material
For a given slot:
1) The server recomputes:
- `H = Argon2id(answer, s)`
  (memory-hard, slow by design)
2) A fingerprint is recomputed:
- `answer_fp = SHA-256(b64(c) || "." || b64(iv) || "." || b64(s))`
This binds the answer attempt to a **specific ciphertext** and prevents reuse.

### 5.3 KMS unwrap via gateway (online, rate-limited)
The server does **not** unwrap the DEK locally.

Instead, it calls the **KMS Gateway API**:
- Endpoint: `POST /kms/unwrap`
- Transport: **mutual TLS**
- Payload includes: `user_id`, `h_b64`, `answer_fp`, and `replicas[] = { kms_id, w_b64 }`

#### Gateway protections
At the gateway level:
- Requests are authenticated using **mTLS**
- Source IPs are restricted (API ↔ KMS allowlist)
  - Each unwrap attempt is rate-limited using Redis:   
    key = lock:unwrap:{user_id}:{answer_fp}   
    TTL = fixed window + jitter

If the same `(user_id + answer_fp)` is retried too soon:
- the gateway responds with **HTTP 429**
- includes `retry_after_seconds`
- no unwrap is attempted

The system fails **closed**:
- Redis errors → unwrap denied
- malformed responses → unwrap denied
- no partial success escalation

### 5.4 Multi-gateway and multi-KMS behavior
API exist in multiple instances per each set of 3 KMS.

For each unwrap attempt:
- gateway URLs are resolved dynamically from DB + environment

From the response:
- only DEKs with valid length (32 bytes) are accepted
- malformed or empty DEKs are discarded

If no DEK is successfully unwrapped:
- the attempt fails
- no information is leaked about which replica failed

### 5.5 Final decryption (current implementation)
Once a valid DEK is obtained:
- The server performs:   
  plaintext = AES-256-GCM(DEK, iv, c)

Plaintext is:
- held only in memory,
- never persisted,
- never logged,
- never cached.

The plaintext is returned to the user for display or editing
and discarded immediately afterward.

> This server-side decrypt is intentional and allows the system
> to enforce attempt limits, lockouts, and timing guarantees centrally.

### 5.6 Security outcome
An attacker attempting to guess answers must:
- interact with the live system,
- respect rate limits,
- wait enforced delays,
- and cannot test guesses offline.

The cost of guessing grows linearly with time, not compute.

### 5.7 Operational isolation and coercion resistance

The KMS Gateway and API components are deployed using credentials
that are not associated with any real individual.

Once deployment is completed:
- SSH access keys are destroyed,
- no interactive access remains,
- and operational control is limited to automated runtime behavior.

As a result:
- no person can modify unwrap behavior post-deployment,
- no operator can selectively bypass rate limits,
- and no coercion of staff or founders can grant access to user data.

This is a deliberate design choice:
the system is structured so that there is no technical authority
capable of revealing Envelope contents, even under pressure.

---

## 6) Rate limiting & lockouts (high-level)

Rate limiting is enforced primarily at unwrap:
- Typical response: HTTP `429` with `retry_after_seconds`
- Key scope: `user_id + answer_fp`

The Symfony app additionally maintains:
- per-note attempt counters (`attemptCount`)
- lockout timers (`lockoutUntil`)
- user-facing messaging for retry timing

These two layers are complementary:
- Gateway throttles cryptographic unwrap attempts.
- App controls UX and longer lockout policy.

---

## 7) KMS replicas & health status

The system is designed for **3 replicas** (`kms1`, `kms2`, `kms3`) to reduce dependence on a single provider.
- Current statuses for each KMS used to encrypt Envelope displayed on Customer dashboard.
- If some replicas are down, re-encrypting the Envelope can restore redundancy (as only healthy replicas will be used).
- In the future we`re going to message Customer if 2 of 3 replicas are down.

---

## 8) KMS Health Gateway API (mTLS)

A dedicated mTLS gateway provides wrap/unwrap and health probing.

### Endpoints
- `POST /kms/wrap`  
  Wrap DEK using `user_id`, `dek_b64`, `h_b64`, `answer_fp`  
  Returns per-KMS results `{ kms_id: { ok, w_b64? } }`

- `POST /kms/unwrap`  
  Unwrap using `user_id`, `h_b64`, `answer_fp`, and provided replicas `{kms_id, w_b64}`  
  Returns per-KMS `{ ok }` and `deks_b64` map

- `GET /kms/health/check`  
  Performs real echo probe (wrap+unwrap) and returns status per KMS

### Security
- mTLS required (`mutualTLS`)
- IP allowlist may be used (gateway-side)
- Rate limiting on unwrap (429)

---

## 9) Open-source verification & key code paths

This project is open source to allow independent verification of the claims above.

Suggested audit targets:
- `CryptoService`
   - server-side libsodium encryption for general fields
   - Envelope triplet decrypt helpers (unwrap + AES-GCM decrypt)

- Note decrypt/edit handlers (server-side plaintext flow):
   - `NoteDecryptHandler`
   - `BeneficiaryNoteDecryptHandler`
   - `NoteEditHandler`
   - `NoteEditTextHandler`

- KMS gateway client + exception mapping:
   - client that calls `/kms/wrap` and `/kms/unwrap`
   - rate-limit exception mapping (`retry_after_seconds`)

---

## 10) Technical FAQ (short)

**Q: If the DB leaks, can an attacker brute-force answers offline?**  
They can attempt offline guessing against Argon2id-derived `H`, but unwrap still requires KMS `KEK`. Without KMS compromise, the attacker cannot validate guesses by unwrapping the DEK. (Online guessing is rate-limited.)

**Q: Why store questions at all?**  
Questions are stored **encrypted** so the UI can show prompts to the user/heir. Answers are never stored.

**Q: Why does the server ever see plaintext during decrypt?**  
Current implementation trades “pure client-side decrypt” for stronger centralized enforcement of attempt limits and lockouts.   
Plaintext is intended to be memory-only and never stored.

## 11) Referral program

The Digital Heir includes a built-in referral program.

- **Lifetime commission:**: earn **20% of all payments** made by customers who register via your referral link or QR code (recurring, for as long as they stay subscribed).
- **Second-level commission:** earn an additional **5% from payments** made by people invited by your referrals.
- **Attribution:**: the referral code is saved on a visitor’s first visit and applied automatically at registration. Cross-device registrations may not be attributed unless the user later logs in from the original device.
- **Rewards & withdrawals:** rewards are credited immediately after payment, but withdrawals follow a 1-month hold period (you can withdraw rewards earned at least one month ago).
- **Payouts:** currently manual — request a withdrawal via the Contact Us page.

---

## Out of scope for this README (kept on website)
- Marketing pages, pricing and general FAQs
- End-user onboarding walkthrough
