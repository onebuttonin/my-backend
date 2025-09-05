<?php

use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client as GuzzleClient;

public function sendOrderMail(Request $request)
{
    $data = $request->validate([
        'email'       => 'required|email',
        'name'        => 'required|string',
        'orderId'     => 'required',
        'trackingId'  => 'nullable|string',
        'templateId'  => 'nullable|integer',
        'orderStatus' => 'nullable|string',
    ]);

    $userEmail  = $data['email'];
    $userName   = $data['name'];
    $orderId    = $data['orderId'];
    $trackingId = $data['trackingId'] ?? null;
    $orderStatus = $data['orderStatus'] ?? 'Order Confirmed';

    // Template map (fallback to env variables if set)
    $map = [
        'Order Confirmed'   => env('BREVO_TEMPLATE_ORDER_CONFIRMED', 1),
        'Shipped'           => env('BREVO_TEMPLATE_SHIPPED', 2),
        'In Transit'        => env('BREVO_TEMPLATE_IN_TRANSIT', 3),
        'Out For Delivery'  => env('BREVO_TEMPLATE_OUT_FOR_DELIVERY', 4),
        'Delivered'         => env('BREVO_TEMPLATE_DELIVERED', 5),
        'Cancelled'         => env('BREVO_TEMPLATE_CANCELLED', 6),
    ];

    // choose templateId: prefer explicit templateId, otherwise map by orderStatus
    $templateId = (int)($data['templateId'] ?? ($map[$orderStatus] ?? env('BREVO_TEMPLATE_ORDER_CONFIRMED', 1)));

    $fromEmail = env('MAIL_FROM_ADDRESS', 'no-reply@yourdomain.com');
    $fromName  = env('MAIL_FROM_NAME', 'Your Brand');

    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
    $api = new TransactionalEmailsApi(new GuzzleClient(), $config);

    // Optional: verify template exists (will throw ApiException if not)
    try {
        if ($templateId) {
            $api->getSmtpTemplate($templateId);
        }
    } catch (ApiException $e) {
        \Log::error('Brevo template check failed', ['id' => $templateId, 'body' => $e->getResponseBody()]);
        return response()->json(['message' => 'Template not accessible', 'details' => $e->getResponseBody()], 422);
    }

    $params = [
        'name' => $userName,
        'orderId' => $orderId,
        'trackingId' => $trackingId,
        'orderStatus' => $orderStatus,
        'currentYear' => date('Y'),
    ];

    $payload = new \SendinBlue\Client\Model\SendSmtpEmail([
        'to' => [['email' => $userEmail, 'name' => $userName]],
        'sender' => ['email' => $fromEmail, 'name' => $fromName],
        'replyTo' => ['email' => env('SUPPORT_EMAIL', 'support@yourdomain.com'), 'name' => 'Support'],
        'templateId' => $templateId,
        'params' => $params,
        'headers' => ['X-App' => 'my-ecommerce-backend', 'X-Order-Id' => (string)$orderId],
        'tags' => ['order-update'],
    ]);

    try {
        $result = $api->sendTransacEmail($payload);
        $messageId = method_exists($result, 'getMessageId') ? $result->getMessageId() : null;
        \Log::info('Brevo sendTransacEmail OK', ['messageId'=>$messageId,'to'=>$userEmail,'template'=>$templateId]);
        return response()->json(['message'=>'Email queued/sent','messageId'=>$messageId], 200);
    } catch (ApiException $e) {
        \Log::error('Brevo send error', ['code'=>$e->getCode(),'body'=>$e->getResponseBody()]);
        return response()->json(['message'=>'Failed to send','details'=>$e->getResponseBody()], 500);
    } catch (\Exception $e) {
        \Log::error('Brevo send Exception', ['err'=>$e->getMessage()]);
        return response()->json(['message'=>'Failed to send','error'=>$e->getMessage()], 500);
    }
}

