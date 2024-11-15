<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Delete;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
class NoteDeleteHandler
{
    private NoteRepository $noteRepository;
    private LoggerInterface $logger;

    public function __construct(NoteRepository $noteRepository, LoggerInterface $logger)
    {
        $this->noteRepository = $noteRepository;
        $this->logger = $logger;
    }

    /**
     * @param NoteDeleteInputDto $input
     *
     * @throws Exception
     */
    public function __invoke(NoteDeleteInputDto $input): void
    {
        $customer = $input->getCustomer();
        $noteId = $input->getNoteId();

        $this->logger->info('Attempting to delete note.', ['noteId' => $noteId, 'customerId' => $customer->getId()]);

        $note = $this->noteRepository->findOneBy([
            'id' => $noteId,
            'customer' => $customer,
        ]);

        if (!$note instanceof Note) {
            $this->logger->warning('Note not found or does not belong to the customer.', ['noteId' => $noteId, 'customerId' => $customer->getId()]);
            throw new AccessDeniedException('You do not have permission to delete this note.');
        }

        try {
            $this->noteRepository->delete($note);
            $this->logger->info('Note deleted successfully.', ['noteId' => $noteId]);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete note.', ['noteId' => $noteId, 'error' => $e->getMessage()]);
            throw new Exception('Failed to delete the note. Please try again later.');
        }
    }
}
