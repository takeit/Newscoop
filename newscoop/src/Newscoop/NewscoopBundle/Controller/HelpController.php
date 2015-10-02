<?php

/**
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace Newscoop\NewscoopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Newscoop\Version;

/**
 * Help controller.
 */
class HelpController extends Controller
{
    /**
     * @Route("/admin/help/")
     */
    public function indexAction(Request $request)
    {
        $newscoop = new \CampVersion();

        return $this->render('NewscoopNewscoopBundle:Help:index.html.twig', array(
            'version' => $newscoop->getVersion(),
            'apiVersion' => Version::API_VERSION,
            'releaseDate' => $newscoop->getReleaseDate(),
        ));
    }
}
