<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoopAnime\UsersBundle\Entity\Users;

/**
 * ViewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ViewsRepository extends EntityRepository
{

    public function getTotViews(Users $user, $completed = true, $idAnime = null)
    {
        $completed = $completed ? 1 : 0;

        $idUser = $user->getId();
        $query = $this->createQueryBuilder("views")
            ->select('COUNT(views.id)')
            ->where("views.idUser = :idUser")
            ->andWhere("views.completed = :completed")
            ->setParameter('idUser',$idUser)
            ->setParameter('completed',$completed);
        if(!empty($idAnime)) {
            $query->join("views.animeEpisodes","ae")
                ->join("ae.animes","a")
                ->andWhere("a.id = :idAnime")
                ->setParameter("idAnime",$idAnime);
        }
        $query = $query->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param Users|null $user
     * @param $idEpisode
     * @return bool
     */
    public function isEpisodeSeen($user, $idEpisode)
    {
        if($user === null)
            return false;
        $r = $this->createQueryBuilder('views')
                ->select('views.id')
                ->where('views.idUser = :idUser')
                ->andWhere('views.idEpisode = :idEpisode')
                ->andWhere('views.completed = 1')
                ->setParameter('idUser',$user->getId())
                ->setParameter('idEpisode',$idEpisode)
                ->getQuery()
                ->getOneOrNullResult();
        if($r !== null) {
            return true;
        }
        return false;
    }

    public function setEpisodeAsSeen(Users $user, $idEpisode, $idLink)
    {
        if(!empty($idEpisode) && !empty($idLink)) {
            $isSeen = false;
            $view = $this->findOneBy(['idUser' => $user->getId(), 'idEpisode' => $idEpisode]);
            if($view !== null)
                $isSeen = $this->isEpisodeSeen($user,$idEpisode);

            // If is set remove -- else insert
            if($isSeen) {
                $view = $this->getView($idEpisode,$user->getId());
                $view->setCompleted(0);
            } else {
                /** @var AnimesEpisodes $episode */
                $episode = $this->getEntityManager()->getRepository('LoopAnimeShowsBundle:AnimesEpisodes')->find($idEpisode);

                if($view === null) {
                    $view = new Views();
                    $view->setIdUser($user->getId());
                    $view->setIdLink($idLink);
                    $view->setIdEpisode($idEpisode);
                    $view->setAnimeEpisodes($episode);
                    $view->setCompleted(1);
                    $view->setWatchedTime(0);
                    $view->setViewTime(new \DateTime("now"));
                } else {
                    $view->setViewTime(new \DateTime("now"));
                    $view->setCompleted(1);
                }
            }
            $this->_em->persist($view);
            $this->_em->flush();
        }

        return true;
    }

    /**
     * @param $idEpisode
     * @param $idUser
     * @return mixed|Views
     */
    private function getView($idEpisode, $idUser)
    {
        $q = $this->createQueryBuilder('views')
                ->select('views')
                ->where('views.idEpisode = :idEpisode')
                ->andWhere('views.idUser = :idUser')
                ->setParameter('idEpisode',$idEpisode)
                ->setParameter('idUser',$idUser)
                ->getQuery();
        return $q->getOneOrNullResult();
    }

    public function setViewProgress(Users $user, $idEpisode, $idLink, $watchedTime)
    {
        if(!empty($idEpisode) && !empty($idLink)) {
            $view = $this->findOneBy(['idUser' => $user->getId(), 'idEpisode' => $idEpisode]);
            $episode = $this->getEntityManager()->getRepository('LoopAnimeShowsBundle:AnimesEpisodes')->find($idEpisode);

            if($view === null) {
                $view = new Views();
                $view->setIdUser($user->getId());
                $view->setIdLink($idLink);
                $view->setIdEpisode($idEpisode);
                $view->setAnimeEpisodes($episode);
                $view->setCompleted(0);
                $view->setWatchedTime((int) $watchedTime);
                $view->setViewTime(new \DateTime("now"));
            } else {
                $view->setWatchedTime((int) $watchedTime);
                $view->setViewTime(new \DateTime("now"));
            }

            $this->_em->persist($view);
            $this->_em->flush();
        } else {
            throw new \Exception("Id Episode and Id Link cannot be empty");
        }
        return true;
    }

    /**
     * @param Users $user
     * @param $idEpisode
     * @throws \Exception
     * @return array|boolean
     */
    public function getViewProgress(Users $user, $idEpisode)
    {
        if(!empty($idEpisode)) {
            /** @var Views $view */
            $view = $this->findOneBy(['idUser' => $user->getId(), 'idEpisode' => $idEpisode]);

            if($view === null) {
                return false;
            } else {
                return [
                    'idLink' => $view->getIdLink(),
                    'watchedTime' => $view->getWatchedTime(),
                    'viewTime' => $view->getViewTime()->format("d-m-Y H:m:s")
                ];
            }
        } else {
            throw new \Exception("Id Episode cannot be empty");
        }
    }


}
