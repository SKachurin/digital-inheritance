# Digital Inheritance
[Link to prototype](https://thedigitalheir.com)

## Overview
Digital Inheritance is a system that ensures important messages are securely delivered to chosen recipients in the event of a user's disappearance or death. It automates periodic checks of your logins in Social media platforms and follows a structured pipeline of actions, including sending WhatsApp messages and emails to confirm the user's well-being. If no response is received, the system releases encrypted messages to designated heirs.

## Features
- **Automated Life Status Checks**: Uses WhatsApp, social media, and email to verify a user's status.
- **Escalating Action Pipeline**: If a user fails to respond within set time intervals, the system escalates actions from reminders to heir notifications.
- **Secure Message Delivery**: Final messages are encrypted and securely sent only when necessary.
- **Dynamic Workflow**: Users can configure pipelines with custom intervals and action sequences.
- **Webhook Support**: Integrates with external messaging services like WhatsApp.
- **Logging and Monitoring**: Ensures transparency and debugging capabilities.

## Workflow
1. **Initial Contact**: The system starts by sending a periodic check-in message (e.g., via WhatsApp or email).
2. **Action Handling**: If the user responds, the pipeline resets. If not, the next scheduled action is triggered.
3. **Escalation**: If the user remains unresponsive, the system follows predefined intervals to escalate the alert level.
4. **Final Message Release**: If no response is received within the final threshold, the system securely sends a last message to the designated heir.

## Key Components
- **Pipelines**: Define action sequences for each user, including message types and intervals.
- **Actions**: Individual steps (e.g., sending an email, checking WhatsApp) within a pipeline.
- **Webhook Handlers**: Process incoming messages and update user status.
- **Cron Jobs**: Execute periodic checks to advance pipelines.

## How It Works
1. User configures a pipeline with time-based actions.
2. The system sends check-in messages at defined intervals.
3. If the user responds, the pipeline resets.
4. If the user does not respond, the system escalates actions.
5. If no response is received by the final step, the inheritance message is released securely to the heir.
