<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateWithSocialProvider;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialCallbackRequest;
use App\Http\Resources\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

final class SocialAuthController extends Controller
{
    /**
     * The provider's consent page URL. The frontend sends the user there.
     */
    public function redirect(SocialProvider $provider): JsonResponse
    {
        $url = Socialite::driver($provider->value)->stateless()->redirect()->getTargetUrl();

        return response()->json(['data' => ['url' => $url]]);
    }

    /**
     * The frontend posts the code it received from the provider and gets an API token.
     */
    public function callback(SocialCallbackRequest $request, SocialProvider $provider, AuthenticateWithSocialProvider $action): AuthTokenResource
    {
        return new AuthTokenResource($action->handle($provider, $request->prepareForDatabase()['device_name']));
    }
}
