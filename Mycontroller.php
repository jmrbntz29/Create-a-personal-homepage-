<?php

namespace App\Http\Controllers;

use MyHelper;
use App\Models\Project;
use App\Models\Property;
use App\Services\LeadApiService;

class ApiController extends Controller
{

    public function __construct() {
    }

    public function enquiryCreate(LeadApiService $leadApiService, Property $property, Project $project)
    {
        $source = request()->source;

        try {
            logger()->useDailyFiles(storage_path('logs/api_enquiries.log'), 'info');
                
            // get data from request
            $data = request()->data ?? request()->all();

            // logs data
            logger()->info('----------------------------------');
            logger()->info('API enquiry : Name >>> '.$source);
            logger()->info('API enquiry : Data >>> '.json_encode($data));

            // load data from request, json decode
            $formData = (object) $data;

            // validate input
            $validate_msg = $leadApiService->validateInput($formData);
            if ($validate_msg) {
                logger()->info('API enquiry : Input Error >>> '.$validate_msg);
                return response(['success' => false, 'msg' => $validate_msg], 400);
            }

            // get ads details by ads_id
            if (str_contains($formData->property_ref, 'nh-') || str_contains($formData->property_ref, 'pj-')) {
                $ads = $project->anyCountry()->active()->where('id', MyHelper::number_only($formData->property_ref))->first();
            } else {
                $ads = $property->anyCountry()->active()->where('id', MyHelper::number_only($formData->property_ref))->first();
            }

            // if not try to mapping with platform id
            if (!$ads) {
                $ads = $property->anyCountry()
                    ->whereHas('user', function ($q) {
                        $q->platformAgent();
                    })
                    ->active()->where('reference', $formData->property_id)->first();
            }

            if (!$ads) {
                logger()->info('API enquiry : The property not available');
                return response(['success' => false, 'msg' => 'The property doesn\'t exists or is not available anymore - EE04'], 400);
            }

            $formData->source = $source;

            return $leadApiService->save($formData, $ads);
            
        } catch (\Exception $e) {
            if (app()->isLocal()) {
                return response(['success' => false, 'msg' => $e->getMessage()], 500);
            }
            logger()->info('API enquiry : Error >>> '.$e->getMessage());
        }
    }

}
