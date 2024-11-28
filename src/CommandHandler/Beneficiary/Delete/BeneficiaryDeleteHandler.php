<?php

declare(strict_types=1);

namespace App\CommandHandler\Beneficiary\Delete;

use App\Entity\Beneficiary;
use App\Repository\BeneficiaryRepository;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
class BeneficiaryDeleteHandler
{
    private BeneficiaryRepository $beneficiaryRepository;
    private LoggerInterface $logger;

    public function __construct(BeneficiaryRepository $beneficiaryRepository, LoggerInterface $logger)
    {
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->logger = $logger;
    }

    /**
     * @param BeneficiaryDeleteInputDto $input
     *
     * @throws Exception
     */
    public function __invoke(BeneficiaryDeleteInputDto $input): void
    {
        $customer = $input->getCustomer();
        $beneficiaryId = $input->getBeneficiaryId();

        $beneficiary = $this->beneficiaryRepository->findOneBy([
            'id' => $beneficiaryId,
            'customer' => $customer,
        ]);

        if (!$beneficiary instanceof Beneficiary) {
            $this->logger->warning('Beneficiary not found or does not belong to the customer.', ['beneficiaryId' => $beneficiaryId, 'customerId' => $customer->getId()]);
            throw new AccessDeniedException('You do not have permission to delete this note.');
        }

        try {
            $this->beneficiaryRepository->delete($beneficiary);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete note.', ['beneficiaryId' => $beneficiaryId, 'error' => $e->getMessage()]);
            throw new Exception('Failed to delete the beneficiary. Please try again later.');
        }
    }
}
