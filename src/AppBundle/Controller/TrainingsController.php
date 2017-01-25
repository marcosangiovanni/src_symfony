<?php

namespace AppBundle\Controller;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Sport;
use CrEOF\Spatial\PHP\Types\Geometry\Point;

class TrainingsController extends FOSRestController
{

	// [GET] /trainings/{id}
	public function getTrainingAction($id)
    {
    	//Find USER By Token
    	$logged_user = $this->get('security.context')->getToken()->getUser();
		
		//Check is granted
		$is_granted = $this->get('security.context')->isGranted('ROLE_USER');
		
		//Find training data
		$training = $this->getDoctrine()->getRepository('AppBundle:Training')->find($id);
		if(!$training){
			throw $this->createNotFoundException('No training found for id '.$id);
		}else{
			$view = $this->view($training, 200);
        	return $this->handleView($view);
		}
    } 
    
	// [GET] /trainings
	// Set search parameters
    public function getTrainingsAction(){

		//Conversione degrees/meters (Earth mean radius)*PI/180
		$conversion_factor = 88222;

		//Find request parameters
		$request = $this->getRequest();
		
		//The training is public or visible only to facebook users
		$is_public = $request->get('is_public');
		
		//The user starting position to search for trainings
		$x = $request->get('x');
		$y = $request->get('y');
		$point = new Point($x,$y);
		
		//The max distance in meters of training
		$max_distance = $request->get('distance');

		//The sports I intend to search within
		$sports = $request->get('sports');

		//The date of training
		//0 = today
		//1 = tomorrow
		//etc...
		$date = $request->get('date');

		//Instantiate the repositiory		
		$repository = $this->getDoctrine()->getRepository('AppBundle:Training');
		
		// Query creation with filters
		$query = $repository->createQueryBuilder('t')

					//Filtering conditions    	
    				->where('t.is_public = :is_public')
    				->andWhere('t.sport_id IN (:sports)')
    				->andWhere('DATE_DIFF(t.start,CURRENT_DATE()) IN (:date)')
    				->andWhere("st_distance(t.position,point(:x_position,:y_position))*:conversion_factor < :max_distance")

					//Order by distance ASC
    				->orderBy("st_distance(t.position,point(:x_position,:y_position))", 'ASC')

					//Parameters passage					
    				->setParameter('is_public', $is_public)

					->setParameter('x_position', $point->getX())
					->setParameter('y_position', $point->getY())
					->setParameter('conversion_factor', $conversion_factor)
					->setParameter('max_distance', $max_distance)

					->setParameter('sports', $sports)
					->setParameter('date', $date)
					
    				->getQuery()
		;
		
		$trainings = $query->getResult();

		if(!$trainings){
			throw $this->createNotFoundException('No collection found');
		}else{
			$view = $this->view($trainings, 200);
        	return $this->handleView($view);
		}
    }

	// "post_trainings"           
	// [POST] /trainings
    public function postTrainingsAction()
    {} 

	// "put_trainings"             
	// [PUT] /trainings/{id}
    public function putTrainingAction($id)
    {}

	// "delete_trainings"          
	// [DELETE] /trainings/{id}
    public function deleteTrainingAction($id)
    {}

}