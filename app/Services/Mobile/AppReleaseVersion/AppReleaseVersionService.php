<?php

namespace App\Services\Mobile\AppReleaseVersion;

use App\Models\V3\MobileAppRelease;
use App\Services\API\V3\BaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class AppReleaseVersionService extends BaseService
{
    const OS_IOS = "ios";
    const OS_ANDROID = "android";
    const IOS_CLIENT_BUNDLE_NAME = "uz.test.testMobile";
    const IOS_MERCHANT_BUNDLE_NAME = "com.test.vendor";
    const ANDROID_CLIENT_BUNDLE_NAME = "uz.test.test_mobile";
    const ANDROID_MERCHANT_BUNDLE_NAME = "uz.solutionslab.test_vendor";

    const IN_UP_UPDATE_STATUS = 3;
    const NO_NEED_UPDATE_STATUS = 1;
    const FLEXIBLE_UPDATE_STATUS = 2;


    /**
     * @param Request $request
     * @return array
     */
    static function getReleaseVersionValidation(Request $request): array
    {
        $headers = [];

        foreach ($request->headers as $key => $item) {
            $headers[$key] = $item[0];
        }

        $validator = Validator::make($headers, [
            'x-mobile-version' => 'required|string',
            'x-mobile-os' => 'required|string|in:android,ios',
            'x-mobile-build' => 'required|string',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    /**
     * @param Request $request
     * @return int
     */
    static function getAppReleaseStatus(Request $request): int
    {
        // TODO: Implement getAppReleaseVersion() method.;
        $headers = self::getReleaseVersionValidation($request);

        //redis check device for getting timeout if client sent same request

        $os = $headers['x-mobile-os'];
        $bundle_name = $headers['x-mobile-build'];
        $client_version = $headers['x-mobile-version'];

        if ($os === self::OS_IOS) {
            if ($bundle_name !== self::IOS_CLIENT_BUNDLE_NAME && $bundle_name !== self::IOS_MERCHANT_BUNDLE_NAME) {
                self::handleError(['Incorrect ios bundle name']);
            }

        }
        if ($os === self::OS_ANDROID) {
            if ($bundle_name !== self::ANDROID_CLIENT_BUNDLE_NAME && $bundle_name !== self::ANDROID_MERCHANT_BUNDLE_NAME) {
                self::handleError(['Incorrect android bundle name']);
            }
        }

        if (!Redis::exists('app:' . $os . ':' . $bundle_name . ":" . $client_version)) {
            return self::NO_NEED_UPDATE_STATUS;
        }

        $version = Redis::get('app:' . $os . ':' . $bundle_name . ":" . $client_version);

        return $version;
    }

    static function saveVerifiedVersionToRedis(): void
    {
        $records = MobileAppRelease::all();

        foreach ($records as $record) {
            if ($record->is_available) {
                Redis::set('app:' . $record->os . ':' . $record->bundle_name . ":" . $record->version, $record->status);
            }
        }
    }
}
