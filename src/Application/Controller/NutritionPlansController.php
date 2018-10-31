<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:38 AM
 */

namespace Application\Controller;


use Model\Entity\Names;
use Model\Entity\NamesCollection;
use Model\Entity\Plan;
use Model\Entity\PlanCollection;
use Model\Entity\ResponseBootstrap;
use Model\Service\NutritionPlansService;
use Symfony\Component\HttpFoundation\Request;

class NutritionPlansController
{

    private $nutritionPlansService;

    public function __construct(NutritionPlansService $nutritionPlansService)
    {
        $this->nutritionPlansService = $nutritionPlansService;
    }


    /**
     * Get plan by id
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($id) && isset($lang) && isset($state)){
            return $this->nutritionPlansService->getPlan($id, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get list of nutrition plans
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getList(Request $request):ResponseBootstrap {
        // get data
        $from = $request->get('from');
        $limit = $request->get('limit');
        $state = $request->get('state');
        $lang = $request->get('lang');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($from) && isset($limit)){ // && isset($state)
            return $this->nutritionPlansService->getListOfNutritionPlans($from, $limit, $state, $lang);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get plans by parametars
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlans(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $like = $request->get('like');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($lang) && !empty($state)){
            return $this->nutritionPlansService->getPlans($lang, $app, $like, $state);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get plans by ids
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIds(Request $request):ResponseBootstrap {
        // get data
        $ids = $request->get('ids');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // to array
        $ids = explode(',', $ids);

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($ids) && !empty($lang) && !empty($state)){
            return $this->nutritionPlansService->getPlansById($ids, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Delete plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function delete(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->nutritionPlansService->deletePlan($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Release plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function postRelease(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];

        // create response object in case of failure
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->nutritionPlansService->releasePlan($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Add plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function post(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $type = $data['type'];
        $names = $data['names'];
        $tags = $data['tags'];
        $recepies = $data['recepies'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setLang($name['language']);
            $temp->setDescription($name['description']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($rawName) && isset($type) && isset($namesCollection) && isset($recepies) && isset($tags) && isset($thumbnail)){
            return $this->nutritionPlansService->createPlan($rawName, $type, $namesCollection, $recepies, $tags, $thumbnail);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Edit plan
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function put(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $type = $data['type'];
        $names = $data['names'];
        $tags = $data['tags'];
        $recepies = $data['recepies'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setLang($name['language']);
            $temp->setDescription($name['description']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id) && isset($rawName) && isset($type) && isset($namesCollection) && isset($recepies) && isset($tags) && isset($thumbnail)){
            return $this->nutritionPlansService->editPlan($id, $rawName, $type, $namesCollection, $recepies, $tags, $thumbnail);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return response
        return $response;
    }


    /**
     * Get total number of nutrition plans
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getTotal(Request $request):ResponseBootstrap {
        // call service for response
        return $this->nutritionPlansService->getTotal();
    }

}