<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\Post;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    const MYSQL_SEARCH_SCORE = 0.09;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function getHomePageArticles($limit = 5)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl', 'p.slug', 'p.createdAt', 'p.publishedAt'])
            ->where('p.showHomePage = true')
            ->andWhere("p.type = 'post'");
        return $queryBuilder->getQuery()->setMaxResults($limit)->getResult();
    }

    public function getArchiveArticles($year, $month, $limit = 20)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('YEAR(p.publishedAt) = :year')
            ->andWhere('MONTH(p.publishedAt) =:month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('p.publishedAt', 'ASC');

        return $queryBuilder->getQuery()->setMaxResults($limit)->getArrayResult();
    }

    /**
     * @param     Tag $tag
     * @param int     $limit
     * @return array
     */
    public function getPostsByTag(Tag $tag, $limit = 20)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $orX = $queryBuilder->expr()->orX();
        $orX->add(':tag MEMBER OF p.tags');
        $queryBuilder
            ->leftJoin('p.tags', 'tags')
            ->andWhere($orX)->setParameter('tag', $tag);

        return $queryBuilder->getQuery()->setMaxResults($limit)->getArrayResult();
    }

    public function searchPostByTitleAndType(string $title)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->andWhere('MATCH_AGAINST(p.title, :title) > :score')
            ->setParameter('title', $title)
            ->setParameter('score', self::MYSQL_SEARCH_SCORE);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param array $criteria
     * @param array $sort
     *
     * @return Pagerfanta
     */
    public function search(array $criteria, array $sort): Pagerfanta
    {
        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($criteria as $field => $value) {
            if (null !== $value) {
                $queryBuilder->andWhere("p.$field = :$field")->setParameter($field, $value);
            }
        }
        $adapter = new DoctrineORMAdapter($queryBuilder);

        return new Pagerfanta($adapter);
    }

    /**
     * @param City $city
     * @return mixed
     */
    public function getPostByCity($city)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->leftJoin('p.city', 'city')
            ->where('p.city  = :city')->setParameter('city', $city);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function resetFeaturedArticles(int $id)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $query = $queryBuilder->update()
            ->set('p.featuredArticle', '?1')
            ->where('p.id != ?2')
            ->setParameter(1, false)
            ->setParameter(2, $id)
            ->getQuery();

        return $query->execute();
    }

    public function getFeaturedArticle()
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl'])
            ->where('p.featuredArticle = true');
        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function getPostByCategory($category, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl', 'p.slug', 'p.createdAt', 'p.publishedAt'])
            ->where('p.category = :category')->setParameter('category', $category)
            ->andWhere("p.type = 'post'");
        return $queryBuilder->getQuery()->setMaxResults($limit)->getResult();
    }

    public function getArticleDetailsBySlug(string $slug)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl', 'p.content', 'p.lang', 'p.allowComment', 'p.commentCount', 'p.viewerCount', 'p.titleSeo', 'p.meta', 'p.keyword', 'p.publishedAt'])
            ->where('p.slug = :slug')->setParameter('slug', $slug);
        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function getRelatedArticles(Post $post, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select(['p.id', 'p.title', 'p.slug', 'p.createdAt', 'p.publishedAt'])
            ->where('p.category = :category')->setParameter('category', $post->getCategory())
            ->andWhere('p.id != :id')->setParameter('id', $post->getId());
        return $queryBuilder->getQuery()->setMaxResults($limit)->getResult();
    }

    public function getPopularArticles($limit = 10)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl', 'p.slug', 'p.createdAt', 'p.publishedAt'])
            ->where('p.popularArticle = true')
            ->andWhere("p.type = 'post'");
        return $queryBuilder->getQuery()->setMaxResults($limit)->getResult();
    }

    public function getLatestArticles($limit = 4)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(['p.id', 'p.title', 'p.summary', 'p.thumbUrl', 'p.slug', 'p.createdAt', 'p.publishedAt'])
            ->andWhere("p.type = 'post'")
            ->orderBy('p.publishedAt', 'DESC');
        return $queryBuilder->getQuery()->setMaxResults($limit)->getResult();
    }
}
