<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ApiRunnerController extends Controller
{
    public function index()
    {
        return view('api-runner');
    }

    public function run(Request $request)
    {
        try {

            $url = $request->url;
            $jsonBody = $request->json_data;

            // Parse user JSON
            $decoded = json_decode($jsonBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Invalid JSON'];
            }

            // Create internal POST request
            $internalRequest = Request::create(
                $url,
                'POST',
                [], // POST fields
                [], // cookies
                [], // files
                [
                    'HTTP_ACCEPT'       => 'application/json',
                    'CONTENT_TYPE'      => 'application/json',
                    'HTTP_CONTENT_TYPE' => 'application/json'
                ]
            );

            // Force JSON body into request
            $internalRequest->setJson($decoded);

            // Attach session (avoids 419)
            $internalRequest->setLaravelSession($request->session());

            // Dispatch through Laravel router
            $response = Route::dispatch($internalRequest);

            return response()->json([
                "status"      => true,
                "status_code" => $response->status(),
                "body"        => json_decode($response->getContent(), true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status"  => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
