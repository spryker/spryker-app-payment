<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Message;

class MessageBuilder
{
    public static function paymentByTransactionIdNotFound(string $transactionId): string
    {
        return sprintf('Could not find a payment with the transaction id "%s"', $transactionId);
    }

    public static function getTransactionIdOrTenantIdentifierMissingOrEmpty(): string
    {
        return 'Either the transactionId or the tenantIdentifier is not set or empty.';
    }

    public static function getInvalidTransactionIdAndTenantIdentifierCombination(): string
    {
        return 'Found a transaction for the requested transactionId but it does not match with the requested tenantIdentifier';
    }

    public static function getRequestTransactionIdIsMissingOrEmpty(): string
    {
        return 'Request transactionId is missing or empty';
    }

    public static function getRequestTenantIdentifierIsMissingOrEmpty(): string
    {
        return 'Request tenantIdentifier is missing or empty';
    }

    public static function getPlatformPluginDoesNotProvideRenderingAPaymentPage(): string
    {
        return 'Platform plugin does not provide rendering a payment page';
    }

    public static function paymentByTenantIdentifierAndOrderReferenceNotFound(string $tenantIdentifier, string $orderReference): string
    {
        return sprintf('Could not find a payment with the tenantIdentifier "%s" and orderReference "%s"', $tenantIdentifier, $orderReference);
    }

    public static function paymentStatusAlreadyInStatus(string $status): string
    {
        return sprintf('Payment status already in "%s".', $status);
    }

    public static function paymentStatusTransitionNotAllowed(string $sourceStatus, string $targetStatus): string
    {
        return sprintf('Payment status transition from "%s" to "%s" is not allowed.', $sourceStatus, $targetStatus);
    }

    public static function refundByRefundIdNotFound(string $refundId): string
    {
        return sprintf('Could not find a refund with the refund id "%s"', $refundId);
    }
}
