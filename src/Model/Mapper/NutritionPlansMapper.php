<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/28/18
 * Time: 9:39 AM
 */

namespace Model\Mapper;

use Model\Entity\PlanCollection;
use PDO;
use PDOException;
use Component\DataMapper;
use Model\Entity\Plan;
use Model\Entity\Shared;

class NutritionPlansMapper extends DataMapper
{

    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * Fetch plan
     *
     * @param Plan $plan
     * @return Plan
     */
    public function getPlan(Plan $plan):Plan {

        // create response object
        $response = new Plan();

        try {
            // set database instructions
            $sql = "SELECT
                       rp.id,
                       rp.raw_name,
                       rp.thumbnail,
                       rp.type,
                       rp.state,
                       rp.version,
                       rpd.description,
                       rpn.name,
                       rpn.language,
                       GROUP_CONCAT(DISTINCT rpr.recepie_id) AS recepie_ids,
                       GROUP_CONCAT(DISTINCT rpt.tag_id) AS tags
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_descriptions AS rpd ON rp.id = rpd.recepie_plans_parent
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    LEFT JOIN recepie_plans_recepies AS rpr ON rp.id = rpr.recepie_plans_parent
                    LEFT JOIN recepie_plans_tags AS rpt ON rp.id = rpt.recepie_plans_parent
                    WHERE rp.id = ?
                    AND rpn.language = ?
                    AND rpd.language = ?
                    AND rp.state = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId(),
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // fetch data
            $data = $statement->fetch();

            // set entity values
            if($statement->rowCount() > 0){
                $response->setId($data['id']);
                $response->setThumbnail($this->configuration['asset_link'] . $data['thumbnail']);
                $response->setName($data['name']);
                $response->setRawName($data['raw_name']);
                $response->setType($data['type']);
                $response->setVersion($data['version']);
                $response->setState($data['state']);
                $response->setDescription($data['description']);
                $response->setLang($data['language']);
                $response->setRecepiesIds($data['recepie_ids']);
                $response->setTags($data['tags']);
            }

        }catch(PDOException $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plan mapper: " . $e->getMessage());
        }

        // return data
        return $response;
    }


    /**
     * Get list of nutrition plans
     *
     * @param Plan $plan
     * @return array
     */
    public function getList(Plan $plan){

        try {

            // get state
            $state = $plan->getState();
            $lang = $plan->getLang();

            // check state and call appropriate query
            if($state === null or $state === ''){
                // set database instructions
                $sql = "SELECT
                       rp.id,
                       rp.thumbnail,
                       rp.raw_name,
                       rp.state,
                       rp.version,
                       rpn.name,
                       rpn.language
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                   /* WHERE rpn.language = 'en' */
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $plan->getFrom();
                $limit = $plan->getLimit();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                // execute query
                $statement->execute();
            }else {
                // set database instructions
                $sql = "SELECT
                       rp.id,
                       rp.thumbnail,
                       rp.raw_name,
                       rp.state,
                       rp.version,
                       rpn.name,
                       rpn.language
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    WHERE rpn.language = :lang AND rp.state = :state   
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set from and limit as core variables
                $from = $plan->getFrom();
                $limit = $plan->getLimit();
                $language = $plan->getLang();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':state', $state);
                $statement->bindParam(':lang', $language);
                // execute query
                $statement->execute();
            }

            // set data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

            // create formatted data variable
            $formattedData = [];

            // loop through data and add link prefixes
            foreach($data as $item){
                $item['thumbnail'] = $this->configuration['asset_link'] . $item['thumbnail'];

                // add formatted item in new array
                array_push($formattedData, $item);
            }

        }catch (PDOException $e){
            $formattedData = [];
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get nutritionplans list mapper: " . $e->getMessage());
        }

        // return data
        return $formattedData;
    }


    /**
     * Fetch plans
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function getPlans(Plan $plan):PlanCollection {

        // create response object
        $planCollection = new PlanCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       rp.id,
                       rp.thumbnail,
                       rp.type,
                       rp.state,
                       rp.version,
                       rpd.description,
                       rpn.name,
                       rpn.language,
                       GROUP_CONCAT(DISTINCT rpr.recepie_id) AS recepie_ids,
                       GROUP_CONCAT(DISTINCT rpt.tag_id) AS tags
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_descriptions AS rpd ON rp.id = rpd.recepie_plans_parent
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    LEFT JOIN recepie_plans_recepies AS rpr ON rp.id = rpr.recepie_plans_parent
                    LEFT JOIN recepie_plans_tags AS rpt ON rp.id = rpt.recepie_plans_parent
                    WHERE rpn.language = ?
                    AND rpd.language = ?
                    AND rp.state = ?
                    GROUP BY rp.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setName($row['name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);
                $plan->setRecepiesIds($row['recepie_ids']);
                $plan->setTags($row['tags']);

                $planCollection->addEntity($plan);
            }

            // set response status
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plans mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Get plans by search term
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function searchPlans(Plan $plan):PlanCollection {

        // create response object
        $planCollection = new PlanCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       rp.id,
                       rp.raw_name,
                       rp.thumbnail,
                       rp.type,
                       rp.state,
                       rp.version,
                       rpd.description,
                       rpn.name,
                       rpn.language,
                       GROUP_CONCAT(DISTINCT rpr.recepie_id) AS recepie_ids,
                       GROUP_CONCAT(DISTINCT rpt.tag_id) AS tags
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_descriptions AS rpd ON rp.id = rpd.recepie_plans_parent
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    LEFT JOIN recepie_plans_recepies AS rpr ON rp.id = rpr.recepie_plans_parent
                    LEFT JOIN recepie_plans_tags AS rpt ON rp.id = rpt.recepie_plans_parent
                    WHERE rpn.language = ?
                    AND rpd.language = ?
                    AND rp.state = ?
                    AND (rpn.name LIKE ? OR rpd.description LIKE ?)
                    GROUP BY rp.id";
            $term = '%' . $plan->getName() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState(),
                $term,
                $term
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setRawName($row['raw_name']);
                $plan->setName($row['name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);
                $plan->setRecepiesIds($row['recepie_ids']);
                $plan->setTags($row['tags']);

                $planCollection->addEntity($plan);
            }

            // set entity values
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search plans mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Fetch plans by ids
     *
     * @param Plan $plan
     * @return PlanCollection
     */
    public function getPlansById(Plan $plan):PlanCollection {

        // Create response object
        $planCollection = new PlanCollection();

        // convert array to comma separated string
        $whereIn = $this->sqlHelper->whereIn($plan->getIds());

        try {
            // set database instructions
            $sql = "SELECT
                       rp.id,
                       rp.raw_name,
                       rp.thumbnail,
                       rp.type,
                       rp.state,
                       rp.version,
                       rpd.description,
                       rpn.name,
                       rpn.language,
                       GROUP_CONCAT(DISTINCT rpr.recepie_id) AS recepie_ids,
                       GROUP_CONCAT(DISTINCT rpt.tag_id) AS tags
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_descriptions AS rpd ON rp.id = rpd.recepie_plans_parent
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    LEFT JOIN recepie_plans_recepies AS rpr ON rp.id = rpr.recepie_plans_parent
                    LEFT JOIN recepie_plans_tags AS rpt ON rp.id = rpt.recepie_plans_parent
                    WHERE rp.id IN (" . $whereIn . ")
                    AND rpn.language = ?
                    AND rpd.language = ?
                    AND rp.state = ?
                    GROUP BY rp.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getLang(),
                $plan->getLang(),
                $plan->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $plan = new Plan();

                // set plan values
                $plan->setId($row['id']);
                $plan->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $plan->setRawName($row['raw_name']);
                $plan->setName($row['name']);
                $plan->setType($row['type']);
                $plan->setVersion($row['version']);
                $plan->setState($row['state']);
                $plan->setDescription($row['description']);
                $plan->setLang($row['language']);
                $plan->setRecepiesIds($row['recepie_ids']);
                $plan->setTags($row['tags']);

                $planCollection->addEntity($plan);
            }

            // set response status
            if($statement->rowCount() == 0){
                $planCollection->setStatusCode(204);
            }else {
                $planCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $planCollection->setStatusCode(204);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get plans by ids mapper: " . $e->getMessage());
        }

        // return data
        return $planCollection;
    }


    /**
     * Delete record
     *
     * @param Plan $plan
     * @return Shared
     */
    public function deletePlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "DELETE
                      rp.*,
                      rpa.*,
                      rpd.*,
                      rpda.*,
                      rpn.*,
                      rpna.*,
                      rpt.*,
                      rpr.*
                    FROM recepie_plans AS rp
                    LEFT JOIN recepie_plans_audit AS rpa ON rp.id = rpa.recepie_plans_parent
                    LEFT JOIN recepie_plans_descriptions AS rpd ON rp.id = rpd.recepie_plans_parent
                    LEFT JOIN recepie_plans_descriptions_audit AS rpda ON rpd.id = rpda.recepie_plans_descriptions_parent
                    LEFT JOIN recepie_plans_names AS rpn ON rp.id = rpn.recepie_plans_parent
                    LEFT JOIN recepie_plans_names_audit AS rpna ON rpn.id = rpna.recepie_plans_names_parent
                    LEFT JOIN recepie_plans_tags AS rpt ON rp.id = rpt.recepie_plans_parent
                    LEFT JOIN recepie_plans_recepies AS rpr ON rp.id = rpr.recepie_plans_parent
                    WHERE rp.id = ?
                    AND rp.state != 'R'";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId()
            ]);

            // set status code
            if($statement->rowCount() > 0){
                $shared->setResponse([200]);
            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Delete plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Release plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function releasePlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "UPDATE 
                      recepie_plans  
                    SET state = 'R'
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getId()
            ]);

            // set response values
            if($statement->rowCount() > 0){
                // set response status
                $shared->setResponse([200]);

                // get latest version value
                $version = $this->lastVersion();

                // set new version
                $sql = "UPDATE recepie_plans SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute(
                    [
                        $version,
                        $plan->getId()
                    ]
                );

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Release plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Add plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function createPlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // get newest id for the version column
            $version = $this->lastVersion();

            // set database instructions for recepie plans table
            $sql = "INSERT INTO recepie_plans
                      (thumbnail, raw_name, type, state, version)
                     VALUES (?,?,?,?,?)";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getThumbnail(),
                $plan->getName(),
                $plan->getType(),
                'P',
                $version
            ]);

            // if first transaction passed continue with rest of inserting
            if($statement->rowCount() > 0){

                // get parent id
                $workoutParent = $this->connection->lastInsertId();

                // insert plan name
                $sqlName = "INSERT INTO recepie_plans_names
                              (name, language, recepie_plans_parent)
                            VALUES (?,?,?)";
                $statementName = $this->connection->prepare($sqlName);

                // insert plan description
                $sqlDescription = "INSERT INTO recepie_plans_descriptions
                                     (description, language, recepie_plans_parent)
                                   VALUES (?,?,?)";
                $statementDescription = $this->connection->prepare($sqlDescription);

                // loop through names collection
                $names = $plan->getNames();
                foreach($names as $name){
                    // execute querys
                    $statementName->execute([
                        $name->getName(),
                        $name->getLang(),
                        $workoutParent
                    ]);

                    $statementDescription->execute([
                        $name->getDescription(),
                        $name->getLang(),
                        $workoutParent
                    ]);
                }

                // insert plans
                $sqlRecepies = "INSERT INTO recepie_plans_recepies
                                (recepie_plans_parent, recepie_id)
                              VALUES (?,?)";
                $statementRecepies = $this->connection->prepare($sqlRecepies);

                // loop through workout ids
                $ids = $plan->getRecepiesIds();
                foreach($ids as $id){
                    // execute query
                    $statementRecepies->execute([
                        $workoutParent,
                        $id
                    ]);
                }

                // insert tags
                $sqlTags = "INSERT INTO recepie_plans_tags
                                (recepie_plans_parent, tag_id)
                              VALUES (?,?)";
                $statementTags = $this->connection->prepare($sqlTags);

                // loop through rounds collection
                $tags = $plan->getTags();
                foreach($tags as $tag){
                    // execute query
                    $statementTags->execute([
                        $workoutParent,
                        $tag
                    ]);
                }

                // set status code
                $shared->setResponse([200]);

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Create plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Update plan
     *
     * @param Plan $plan
     * @return Shared
     */
    public function editPlan(Plan $plan):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // update main recepie plans table
            $sql = "UPDATE recepie_plans SET 
                        thumbnail = ?,
                        raw_name = ?,
                        type = ? 
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $plan->getThumbnail(),
                $plan->getName(),
                $plan->getType(),
                $plan->getId()
            ]);

            // update version
            if($statement->rowCount() > 0){
                // get last version
                $lastVersion = $this->lastVersion();

                // set database instructions
                $sql = "UPDATE recepie_plans SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    $lastVersion,
                    $plan->getId()
                ]);
            }

            // update names query
            $sqlNames = "INSERT INTO
                            recepie_plans_names (name, language, recepie_plans_parent)
                            VALUES (?,?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            name = VALUES(name),
                            language = VALUES(language),
                            recepie_plans_parent = VALUES(recepie_plans_parent)";
            $statementNames = $this->connection->prepare($sqlNames);

            // update description query
            $sqlDescription = "INSERT INTO
                                    recepie_plans_descriptions (description, language, recepie_plans_parent)
                                    VALUES (?,?,?)
                                ON DUPLICATE KEY
                                UPDATE
                                    description = VALUES(description),
                                    language = VALUES(language),
                                    recepie_plans_parent = VALUES(recepie_plans_parent)";
            $statementDescription = $this->connection->prepare($sqlDescription);

            // loop through data and make updates if neccesary
            $names = $plan->getNames();
            foreach($names as $name){
                // execute name query
                $statementNames->execute([
                    $name->getName(),
                    $name->getLang(),
                    $plan->getId()
                ]);

                // execute description query
                $statementDescription->execute([
                    $name->getDescription(),
                    $name->getLang(),
                    $plan->getId()
                ]);
            }


            // delete recepie ids
            $sqlDeleteRecepies = "DELETE FROM recepie_plans_recepies WHERE recepie_plans_parent = ?";
            $statementDeleteRecepies = $this->connection->prepare($sqlDeleteRecepies);
            $statementDeleteRecepies->execute([
                $plan->getId()
            ]);

            // update recepie ids
            $sqlRecepies = "INSERT INTO
                                recepie_plans_recepies (recepie_plans_parent, recepie_id)
                                VALUES (?,?)
                            ON DUPLICATE KEY
                            UPDATE
                                recepie_plans_parent = VALUES(recepie_plans_parent),
                                recepie_id = VALUES(recepie_id)";
            $statementRecepies = $this->connection->prepare($sqlRecepies);

            // loop through data and make updates if neccesary
            $ids = $plan->getRecepiesIds();
            foreach($ids as $id){
                // execute query
                $statementRecepies->execute([
                    $plan->getId(),
                    $id
                ]);
            }


            // delete tag ids
            $sqlDeleteTags = "DELETE FROM recepie_plans_tags WHERE recepie_plans_parent = ?";
            $statementDeleteTags = $this->connection->prepare($sqlDeleteTags);
            $statementDeleteTags->execute([
                $plan->getId()
            ]);

            // update tags
            $sqlTags = "INSERT INTO
                            recepie_plans_tags (recepie_plans_parent, tag_id)
                            VALUES (?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            recepie_plans_parent = VALUES(recepie_plans_parent),
                            tag_id = VALUES(tag_id)";
            $statementTags = $this->connection->prepare($sqlTags);

            // loop through data and make updates if neccesary
            $tags = $plan->getTags();
            foreach($tags as $tag){
                // execute query
                $statementTags->execute([
                    $plan->getId(),
                    $tag
                ]);
            }

            // commit transaction
            $this->connection->commit();

            // set status code
            $shared->setResponse([200]);

        }catch(PDOException $e){
            // rollback everything n case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Edit plan mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Get total number of nutrition plans
     *
     * @return null
     */
    public function getTotal() {

        try {
            // set database instructions
            $sql = "SELECT COUNT(*) as total FROM recepie_plans";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            // set total number
            $total = $statement->fetch(PDO::FETCH_ASSOC)['total'];

        }catch(PDOException $e){
            $total = null;

            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get total plans mapper: " . $e->getMessage());
        }

        // return data
        return $total;
    }


    /**
     * Get last version number
     *
     * @return string
     */
    public function lastVersion(){
        // set database instructions
        $sql = "INSERT INTO version VALUES(null)";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        // fetch id
        $lastId = $this->connection->lastInsertId();

        // return last id
        return $lastId;
    }

}