<?php

namespace App\Form\DataTransformer;

use App\Enum\TransformableEnumInterface;
use BackedEnum;
use App\Enum\CustomerSocialAppEnum;
use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\IntervalEnum;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<BackedEnum|null, string|null>
 */
class EnumToStringTransformer implements DataTransformerInterface
{
    /**
     * Transforms an enum to its string representation.
     *
     * @param CustomerSocialAppEnum|null $value
     *
     * @return string|null
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof CustomerSocialAppEnum) {
            throw new TransformationFailedException('Expected a CustomerSocialAppEnum.');
        }

        return $value->value;
    }

    /**
     * Transforms a string to its enum representation.
     *
     * @param string|null $value
     *
     * @return CustomerSocialAppEnum
     */
    public function reverseTransform($value): CustomerSocialAppEnum
    {
        if (null === $value) {
            return CustomerSocialAppEnum::NONE; // Default value
        }

        try {
            return CustomerSocialAppEnum::from($value);
        } catch (\ValueError $e) {
            throw new TransformationFailedException(sprintf('The value "%s" is not a valid CustomerSocialAppEnum.', $value));
        }
    }
}