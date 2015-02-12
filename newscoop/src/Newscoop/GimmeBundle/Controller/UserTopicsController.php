<?php
/**
 * @package Newscoop\GimmeBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\GimmeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Newscoop\NewscoopBundle\Entity\Topic;
use Newscoop\Exception\InvalidParametersException;

class UserTopicsController extends FOSRestController
{
    /**
     * Get topics followed by user
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404="Returned when topics are not found"
     *     },
     *     parameters={
     *         {"name"="language", "dataType"="string", "required"=false, "description"="Language code"}
     *     },
     *     requirements={
     *         {"name"="userId", "dataType"="integer", "required"=true, "description"="User Id"}
     *     }
     * )
     *
     * @Route("/userTopics/{userId}.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     *
     * @return array
     */
    public function getUserTopicsAction(Request $request, $userId)
    {
        $em = $this->get('em');
        $user = $em->getRepository('Newscoop\Entity\User')->findOneById($userId);
        if (!$user) {
            throw new NotFoundHttpException('User was not found');
        }

        $userTopicsService = $this->get('user.topic');
        $userTopics = $userTopicsService->getTopics($user, $request->get('language'));

        if (empty($userTopics)) {
            throw new NotFoundHttpException('Result was not found.');
        }

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $userTopics = $paginator->paginate($userTopics, array(
            'distinct' => false
        ));

        return $userTopics;
    }

    /**
     * Link topic to user
     *
     * **topics headers**:
     *
     *     header name: "link"
     *     header value: "</api/topics/1; rel="topic">"
     *
     *
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         404="Returned when resource not found",
     *         409={
     *           "Returned when the link already exists",
     *         }
     *     },
     *     requirements={
     *         {"name"="userId", "dataType"="integer", "required"=true, "description"="User Id"}
     *     }
     * )
     *
     * @Route("/userTopics/{userId}.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("LINK")
     * @View(statusCode=201)
     */
    public function linkTopicToUserAction(Request $request, $userId)
    {
        $em = $this->container->get('em');
        $user = $em->getRepository('Newscoop\Entity\User')->findOneById($userId);

        if (!$user) {
            throw new NotFoundHttpException('User was not found');
        }

        $matched = false;
        foreach ($request->attributes->get('links', array()) as $key => $objectArray) {
            if (!is_array($objectArray)) {
                return true;
            }

            $resourceType = $objectArray['resourceType'];
            $object = $objectArray['object'];

            if ($object instanceof \Exception) {
                throw $object;
            }

            if ($object instanceof \Newscoop\NewscoopBundle\Entity\Topic) {
                $userTopicService = $this->get('user.topic');
                $userTopicService->followTopic($user, $object);

                $matched = true;

                continue;
            }
        }

        if ($matched === false) {
            throw new InvalidParametersException('Any supported link object not found');
        }
    }
}