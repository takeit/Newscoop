<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityManager;

/**
 * Datatable repository decorator
 */
class DatatableRepository
{
    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    /** @var Doctrine\ORM\EntityRepository */
    protected $repository;

    /** @var string */
    protected $entityName;

    /**
     * @param string $entityName
     */
    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = (string) $entityName;
        $this->repository = $this->em->getRepository($this->entityName);
    }

    /**
     * Get data for table
     *
     * @param array $params
     * @param array $cols
     * @return array
     */
    public function getData(array $params, array $cols)
    {
        static $default_params = array(
            'iDisplayStart' => 0,
            'iDisplayLength' => 25,
        );

        $params += $default_params;

        $qb = $this->repository->createQueryBuilder('e');

        if (!empty($params['sSearch'])) {
            $qb->where($this->buildWhere($cols, $params));
        }

        // sort
        foreach (array_keys($cols) as $id => $property) {
            if (!is_string($property)) { // not sortable
                continue;
            }

            if (isset($params["iSortCol_$id"])) {
                $dir = $params["sSortDir_$id"] ?: 'asc';
                $qb->orderBy("e.$property", $dir);
            }
        }

        // limit
        $qb->setFirstResult((int) $params['iDisplayStart'])
            ->setMaxResults((int) $params['iDisplayLength']);


        return $qb->getQuery()->getResult();
    }

    /**
     * Get user count
     *
     * @return int
     */
    public function getCount()
    {
        return $this->em
            ->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($this->entityName, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get filtered count
     *
     * @param array $params
     * @param array $cols
     * @return int
     */
    public function getFilteredCount(array $params, array $cols)
    {
        if (empty($params['sSearch'])) {
            return $this->getCount();
        }

        return $this->em
            ->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($this->entityName, 'e')
            ->where($this->buildWhere($cols, $params['sSearch']))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Build where condition
     *
     * @param array $cols
     * @return Doctrine\ORM\Query\Expr
     */
    private function buildWhere(array $cols, $params)
    {
        $qb = $this->repository->createQueryBuilder('e');
        $or = $qb->expr()->orx();
        
        $reflection = new \ReflectionObject(new $this->entityName);
        
        $search = $params['sSearch'];
        foreach (array_keys($cols) as $id => $property) {
        	//column is searchable
            if ($reflection->hasProperty($property) && $params["bSearchable_{$id}"]) {
                $or->add($qb->expr()->like("e.$property", $qb->expr()->literal("%{$search}%")));
            }     
        }

        return $or;
    }
}
