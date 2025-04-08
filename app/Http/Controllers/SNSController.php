<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class SNSController extends Controller
{
    /**
     * SNSトピック経由でメール通知を送信する
     *
     * @param string $message メッセージ内容
     * @param string $subject メールの件名
     * @return bool 送信成功した場合はtrue、失敗した場合はfalse
     */
    public function sendNotification(string $message, string $subject = 'DebateMatch通知')
    {
        // トピックARNを環境変数から取得
        $topicArn = env('AWS_SNS_NOTIFICATION_TOPIC_ARN');

        // トピックARNが設定されていない場合は処理終了
        if (!$topicArn) {
            Log::warning('SNS通知トピックARNが設定されていません。(AWS_SNS_NOTIFICATION_TOPIC_ARN)');
            return false;
        }

        try {
            // SNSクライアントを取得
            $snsClient = App::make('aws')->createClient('sns');

            // トピックにメッセージを発行
            $result = $snsClient->publish([
                'TopicArn' => $topicArn,
                'Message' => $message,
                'Subject' => $subject,
            ]);

            Log::info("メール通知送信成功: TopicArn={$topicArn}, MessageId={$result['MessageId']}");
            return true;
        } catch (AwsException $e) {
            Log::error("メール通知送信失敗: TopicArn={$topicArn}, Error={$e->getMessage()}");
            if ($e->getAwsErrorCode()) {
                Log::error("AWS Error Code: " . $e->getAwsErrorCode());
            }
            return false;
        } catch (\Exception $e) {
            Log::error("メール通知送信中に予期せぬエラーが発生: Error={$e->getMessage()}");
            return false;
        }
    }
}
