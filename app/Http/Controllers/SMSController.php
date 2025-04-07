<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class SMSController extends Controller
{
    /**
     * @param string $phoneNumber
     * @param string $message
     * @return \Aws\Result|bool
     */
    public function sendSms(string $phoneNumber, string $message)
    {
        $snsClient = App::make('aws')->createClient('sns');

        try {
            // SMSメッセージを送信
            $result = $snsClient->publish([
                'Message' => $message,
                'PhoneNumber' => $phoneNumber,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType' => 'String',
                        'StringValue' => 'Transactional',
                    ],
                ],
            ]);

            Log::info("SMS送信成功: PhoneNumber={$phoneNumber}, MessageId={$result['MessageId']}");
            return $result;
        } catch (AwsException $e) {
            Log::error("SMS送信失敗: PhoneNumber={$phoneNumber}, Error={$e->getMessage()}");
            return false;
        } catch (\Exception $e) {
            Log::error("SMS送信中に予期せぬエラーが発生: PhoneNumber={$phoneNumber}, Error={$e->getMessage()}");
            return false;
        }
    }
}
