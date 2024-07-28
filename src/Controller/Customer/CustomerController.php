<?php

declare(strict_types=1);

namespace App\Controller\Customer;

//use App\CommandHandler\Action\Create\ActionCreateOutputDto;
//use App\CommandHandler\Action\Create\ActionCreateInputDto;
//use App\CommandHandler\Action\GetAllByOffer\ActionByOfferOutputDto;
//use App\CommandHandler\Action\GetAllByOffer\ActionsFetchByOfferInputDto;
//
//use App\Repository\Collection\PageCollection;
//use Nelmio\ApiDocBundle\Annotation\Model;
//use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
//use Symfony\Component\Messenger\Stamp\HandledStamp;
//use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Messenger\MessageBusInterface;
//use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Component\Uid\Uuid;

class CustomerController extends AbstractController
{

//    public function __construct(
//        private SerializerInterface $serializer,
//        private MessageBusInterface $commandBus
//    ) {}
//
//    #[Route('/customer/create', name: 'customer-create', methods: ['POST'])]
//
//    public function create(
//        Request $request
//    ): Response {
//        $inputDto = $this->serializer->deserialize(
//            $request->getContent(),
//            ActionCreateInputDto::class,
//            'json',
//        );
//
//        $envelope = $this->commandBus->dispatch($inputDto);
//
//        $handledStamp = $envelope->last(HandledStamp::class);
//
//        if (!$handledStamp) {
//            throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
//        }
//
//        /** @var ActionCreateOutputDto $responseData */
//        $responseData = $handledStamp->getResult();
//        $response = $this->sendResponse(
//            success: true,
//            data: $responseData,
//            statusCode: 201
//        );
//
//        $this->getErrorResponses();
//
//        return $response;
//    }
}
