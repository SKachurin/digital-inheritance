<?php

namespace App\Service;

use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageProcessorService
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
        private readonly PipelineRepository $pipelineRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
//        private readonly LoggerInterface $logger
    ) {}

    public function processMessage(string $sender, string $text, callable $sendMessage): void
    {
        $action = $this->actionRepository->findOneBy(['chatId' => $sender]);

        if (!$action) {
            return;
        }

        $customer = $action->getCustomer();
        $lang = $customer?->getLang() ?? 'en';

        $okayPassword = $customer->getCustomerOkayPassword();
        $contact = $action->getContact();
        $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);

        if (!$pipeline) {
            return;
        }

        if ($pipeline->getActionStatus() !== ActionStatusEnum::PENDING || $action->getActionType() !== $pipeline->getActionType()) {

            $sendMessage($contact, $this->translator->trans('messages.not_time', [], 'messages', $lang)); // 😲
            return;
        }

        $words = array_slice(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY), 0, 50);

        foreach ($words as $word) {
            if (password_verify($word, $okayPassword)) {
                $this->resetPipeline($pipeline, $sendMessage, $contact, $lang);
                return;
            }
        }

        $sendMessage($contact, $this->translator->trans('messages.not_right', [], 'messages', $lang)); // 🤔
    }

    private function resetPipeline($pipeline, callable $sendMessage, $contact, string $lang): void
    {
        $actionSequence = $pipeline->getActionSequence();
        $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);

        if (!empty($firstAction)) {
            $fa = reset($firstAction);
            if (!isset($fa['actionType'])) {
                return;
            }

            $pipeline->setActionType(ActionTypeEnum::from($fa['actionType']));
            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);

            $this->entityManager->persist($pipeline);
            $this->entityManager->flush();

            $sendMessage($contact, $this->translator->trans('messages.resetting', [], 'messages', $lang)); // 😊
        }
    }
}
// Version for Redis

//namespace App\Service;
//
//use App\Enum\ActionStatusEnum;
//use App\Enum\ActionTypeEnum;
//use App\Repository\ActionRepository;
//use App\Repository\PipelineRepository;
//use Doctrine\ORM\EntityManagerInterface;
//use Psr\Log\LoggerInterface;
//use Symfony\Contracts\Cache\CacheInterface;
//
//class MessageProcessorService
//{
//    private const MAX_ATTEMPTS = 5; // Max incorrect attempts before lock
//    private const FIRST_LOCK_TIME = 60; // Initial lock duration in minutes
//
//    public function __construct(
//        private readonly ActionRepository $actionRepository,
//        private readonly PipelineRepository $pipelineRepository,
//        private readonly EntityManagerInterface $entityManager,
//        private readonly LoggerInterface $logger,
//        private readonly CacheInterface $cache // Redis/Symfony Cache
//    ) {}
//
//    public function processMessage(string $sender, string $text, callable $sendMessage): void
//    {
//        $cacheKey = "failed_attempts_{$sender}";
//
//        // Get current failed attempts from Redis
//        $failedAttempts = $this->cache->get($cacheKey, fn() => 0);
//        $now = new \DateTimeImmutable();
//
//        // If sender is locked out, return early
//        if ($failedAttempts >= self::MAX_ATTEMPTS) {
//            $remainingLockTime = $this->cache->get("lockout_{$sender}", fn() => 0);
//            $sendMessage($sender, "You've tried too many times. Try again in {$remainingLockTime} minutes ⏳.");
//            return;
//        }
//
//        // Find action linked to sender (email/chatId)
//        $action = $this->actionRepository->findOneBy(['chatId' => $sender]);
//        if (!$action) {
//            return;
//        }
//
//        $customer = $action->getCustomer();
//        if (!$customer) {
//            return;
//        }
//
//        $okayPassword = $customer->getCustomerOkayPassword();
//        $contact = $action->getContact();
//        $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);
//
//        if (!$pipeline || $pipeline->getActionStatus() !== ActionStatusEnum::PENDING) {
//            $sendMessage($contact, "Now's not the time \u{1F632}"); // 😲
//            return;
//        }
//
//        $words = array_slice(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY), 0, 50);
//
//        foreach ($words as $word) {
//            if (password_verify($word, $okayPassword)) {
//                // Reset failed attempts on success
//                $this->cache->delete($cacheKey);
//                $this->resetPipeline($pipeline, $sendMessage, $contact);
//                return;
//            }
//        }
//
//        // Log failed attempt and apply cooldown logic
//        $failedAttempts++;
//        if ($failedAttempts >= self::MAX_ATTEMPTS) {
//            $lockTime = ($failedAttempts - self::MAX_ATTEMPTS + 1) * self::FIRST_LOCK_TIME;
//            $this->cache->get("lockout_{$sender}", fn() => $lockTime, $lockTime * 60);
//            $this->cache->get($cacheKey, fn() => self::MAX_ATTEMPTS, $lockTime * 60);
//            $sendMessage($contact, "Too many incorrect attempts! Try again in {$lockTime} minutes. ⏳");
//        } else {
//            // Store failed attempts in Redis (auto-expiring)
//            $this->cache->get($cacheKey, fn() => $failedAttempts, 3600); // Auto-expire in 1 hour
//            $sendMessage($contact, "Incorrect password! Attempt {$failedAttempts} of " . self::MAX_ATTEMPTS);
//        }
//    }
//
//    private function resetPipeline($pipeline, callable $sendMessage, $contact): void
//    {
//        $actionSequence = $pipeline->getActionSequence();
//        $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);
//
//        if (!empty($firstAction)) {
//            $fa = reset($firstAction);
//            if (!isset($fa['actionType'])) {
//                return;
//            }
//
//            $pipeline->setActionType(ActionTypeEnum::from($fa['actionType']));
//            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);
//
//            $this->entityManager->persist($pipeline);
//            $this->entityManager->flush();
//
//            $sendMessage($contact, "It's nice to hear it \u{1F60A}!"); // 😊
//        }
//    }
//}

