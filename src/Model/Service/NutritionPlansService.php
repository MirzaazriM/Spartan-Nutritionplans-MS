<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:38 AM
 */

namespace Model\Service;


use Component\LinksConfiguration;
use Model\Core\Helper\Monolog\MonologSender;
use Model\Entity\NamesCollection;
use Model\Entity\Plan;
use Model\Entity\PlanCollection;
use Model\Entity\ResponseBootstrap;
use Model\Mapper\NutritionPlansMapper;
use Model\Service\Facade\GetPlansFacade;
use Exception;

class NutritionPlansService extends LinksConfiguration
{

    private $nutritionPlansMapper;
    private $configuration;
    private $monologHelper;

    public function __construct(NutritionPlansMapper $nutritionPlansMapper)
    {
        $this->nutritionPlansMapper = $nutritionPlansMapper;
        $this->configuration = $nutritionPlansMapper->getConfiguration();
        $this->monologHelper = new MonologSender();
    }


    /**
     * Get plan by id
     *
     * @param int $id
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlan(int $id, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response
            $res = $this->nutritionPlansMapper->getPlan($entity);
            $id = $res->getId();

            // get tags ids
            $tagIds = $res->getTags();
            // call tags MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            // get recepies ids
            $ids = $res->getRecepiesIds();
            // call recepies MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['recepies_url'] . '/recepie/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
            // set data to variable
            $recepiesData = $result->getBody()->getContents();

            // check data and set response
            if(isset($id)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    'id' => $res->getId(),
                    'thumbnail' => $res->getThumbnail(),
                    'name' => $res->getName(),
                    'raw_name' => $res->getRawName(),
                    'description' => $res->getDescription(),
                    'language' => $res->getLang(),
                    // 'state' => $res->getState(),
                    'version' => $res->getVersion(),
                    'tags' => json_decode($tags),
                    'recepies' => json_decode($recepiesData)
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Get list of nutrition plans
     *
     * @param int $from
     * @param int $limit
     * @return ResponseBootstrap
     */
    public function getListOfNutritionPlans(int $from, int $limit, string $state = null, string $lang = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setFrom($from);
            $entity->setLimit($limit);
            $entity->setState($state);
            $entity->setLang($lang);

            // call mapper for data
            $data = $this->nutritionPlansMapper->getList($entity);

            // set response according to data content
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get nutritionplans list service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get plans
     *
     * @param string $lang
     * @param string|null $app
     * @param string|null $like
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlans(string $lang, string $app = null, string $like = null, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create facade and call its functions for data
            $facade = new GetPlansFacade($lang, $app, $like, $state, $this->nutritionPlansMapper);
            $res = $facade->handlePlans();

            // check if data needs to be formatted
            if(gettype($res) === 'object'){
                // convert data to array for appropriate response
                $data = [];

                for($i = 0; $i < count($res); $i++){
                    $data[$i]['id'] = $res[$i]->getId();
                    $data[$i]['name'] = $res[$i]->getName();
                    $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                    $data[$i]['description'] = $res[$i]->getDescription();
                    // $data[$i]['language'] = $res[$i]->getLang();
                    // $data[$i]['type'] = $res[$i]->getType();
                    $data[$i]['version'] = $res[$i]->getVersion();
                    // $data[$i]['state'] = $res[$i]->getState();

                    // get tags ids
                    $tagIds = $res[$i]->getTags();
                    // call tags MS for data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                    $tags = $result->getBody()->getContents();

                    $data[$i]['tags'] = json_decode($tags);

                    // get recepies ids
                    $ids = $res[$i]->getRecepiesIds();
                    // call recepies MS for data
                    $client = new \GuzzleHttp\Client();
                    $result = $client->request('GET', $this->configuration['recepies_url'] . '/recepie/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
                    // set data to variable
                    $recepiesData = $result->getBody()->getContents();

                    $data[$i]['recepies'] = json_decode($recepiesData);
                }
            }else if(gettype($res) === 'array') {
                $data = $res;
            }


            // Check Data and Set Response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plans service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get plans by ids
     *
     * @param array $ids
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlansById(array $ids, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setIds($ids);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response
            $res = $this->nutritionPlansMapper->getPlansById($entity);

            // convert data to array for appropriate response
            $data = [];

            for($i = 0; $i < count($res); $i++){
                $data[$i]['id'] = $res[$i]->getId();
                $data[$i]['raw_name'] = $res[$i]->getRawName();
                $data[$i]['name'] = $res[$i]->getName();
                $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                $data[$i]['description'] = $res[$i]->getDescription();
                $data[$i]['version'] = $res[$i]->getVersion();
                // $data[$i]['state'] = $res[$i]->getState();

                // get tags ids
                $tagIds = $res[$i]->getTags();
                // call tags MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                $tags = $result->getBody()->getContents();

                $data[$i]['tags'] = json_decode($tags);

                // get recepies ids
                $ids = $res[$i]->getRecepiesIds();
                // call recepies MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['recepies_url'] . '/recepie/ids?lang=' .$lang. '&state=R' . '&ids=' .$ids, []);
                // set data to variable
                $recepiesData = $result->getBody()->getContents();

                $data[$i]['recipes'] = json_decode($recepiesData);
            }

            // Check Data and Set Response
            if($res->getStatusCode() == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch(\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get plans by ids service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }

    }


    /**
     * Delete plan
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function deletePlan(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);

            // get response
            $res = $this->nutritionPlansMapper->deletePlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Release plan service
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function releasePlan(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);

            // get response
            $res = $this->nutritionPlansMapper->releasePlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Release plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Add nutrition plan
     *
     * @param string $rawName
     * @param string $type
     * @param NamesCollection $names
     * @param array $recepies
     * @param array $tags
     * @param string $thumbnail
     * @return ResponseBootstrap
     */
    public function createPlan(string $rawName, string $type, NamesCollection $names, array $recepies, array $tags, string $thumbnail):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setName($rawName);
            $entity->setType($type);
            $entity->setRecepiesIds($recepies);

            // get response
            $res = $this->nutritionPlansMapper->createPlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Create plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Edit plan service
     *
     * @param int $id
     * @param string $rawName
     * @param string $type
     * @param NamesCollection $names
     * @param array $recepies
     * @param array $tags
     * @param $thumbnail
     * @return ResponseBootstrap
     */
    public function editPlan(int $id, string $rawName, string $type, NamesCollection $names, array $recepies, array $tags, $thumbnail):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Plan();
            $entity->setId($id);
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setNames($names);
            $entity->setName($rawName);
            $entity->setType($type);
            $entity->setRecepiesIds($recepies);

            // get response
            $res = $this->nutritionPlansMapper->editPlan($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Edit plan service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get total
     *
     * @return ResponseBootstrap
     */
    public function getTotal():ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // call mapper for data
            $data = $this->nutritionPlansMapper->getTotal();

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    $data
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get total plans service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }

}