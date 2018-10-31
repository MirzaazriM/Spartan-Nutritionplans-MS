<?php

namespace Model\Service\Facade;

use Model\Entity\Plan;
use Model\Entity\PlanCollection;
use Model\Mapper\NutritionPlansMapper;

class GetPlansFacade
{

    private $lang;
    private $app;
    private $like;
    private $state;
    private $plansMapper;
    private $configuration;

    public function __construct(string $lang, string $app = null, string $like = null, string $state, NutritionPlansMapper $plansMapper) {
        $this->lang = $lang;
        $this->app = $app;
        $this->like = $like;
        $this->state = $state;
        $this->plansMapper = $plansMapper;
        $this->configuration = $plansMapper->getConfiguration();
    }


    /**
     * Handle nutrition plans
     *
     * @return mixed|PlanCollection|null
     */
    public function handlePlans() {
        $data = null;

        // Calling By App
        if(!empty($this->app)){
            $data = $this->getPlansByApp();
        }
        // Calling by Search
        else if(!empty($this->like)){
            $data = $this->searchPlans();
        }
        // Calling by State
        else{
            $data = $this->getPlans();
        }

        // return data
        return $data;
    }


    /**
     * Get plans
     *
     * @return PlanCollection
     */
    public function getPlans():PlanCollection {
        // create entity and set its values
        $entity = new Plan();
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // call mapper for data
        $collection = $this->plansMapper->getPlans($entity);

        // return data
        return $collection;
    }


    /**
     * Get plans by app
     *
     * @return mixed
     */
    public function getPlansByApp() {
        // call apps MS for data
        $client = new \GuzzleHttp\Client();
        $result = $client->request('GET', $this->configuration['apps_url'] . '/apps/data?app=' . $this->app . '&lang=' . $this->lang . '&state=' . $this->state . '&type=nutrition_plans', []);
        $data = json_decode($result->getBody()->getContents(), true);

        // return data
        return $data;
    }


    /**
     * Search plans
     *
     * @return PlanCollection
     */
    public function searchPlans():PlanCollection {
        // create entity and set its values
        $entity = new Plan();
        $entity->setLang($this->lang);
        $entity->setState($this->state);
        $entity->setName($this->like);

        // call mapper for data
        $data = $this->plansMapper->searchPlans($entity);

        // return data
        return $data;
    }

}