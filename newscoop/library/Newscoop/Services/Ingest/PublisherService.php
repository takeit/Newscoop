<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Services\Ingest;

use Newscoop\Entity\Ingest\Feed\Entry;

/**
 * Ingest publisher service
 */
class PublisherService
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Publish entry
     *
     * @param Newscoop\Entity\Ingest\Feed\Entry $entry
     * @return Article
     */
    public function publish(Entry $entry)
    {
        $article = new \Article($this->getLanguage($entry->getLanguage()));
        $article->create($this->config['article_type'], $entry->getTitle(), $this->getPublication(), $this->getIssue(), $this->getSection($entry));
        $article->setWorkflowStatus('Y');
        $this->setArticleData($article, $entry);
        $this->setArticleDates($article, $entry);
        return $article;
    }

    /**
     * Get language
     *
     * @param string $code
     * @return int
     */
    private function getLanguage($code)
    {
         $languages = \Language::GetLanguages(null, $code);
         if (empty($languages)) {
             throw new \InvalidArgumentException("Language with code '$code' not found");
         }

         return array_shift($languages)->getLanguageId();
    }

    /**
     * Get publication
     *
     * @return int
     */
    private function getPublication()
    {
        $publications = $GLOBALS['Campsite']['publications'];
        if (empty($publications)) {
            throw new \RuntimeException("No publications defined.");
        }

        return (int) $publications[count($publications) - 1]->getPublicationId();
    }

    /**
     * Get issue
     *
     * @return int
     */
    private function getIssue()
    {
        return (int) \Issue::GetCurrentIssue($this->getPublication())->getIssueNumber();
    }

    /**
     * Get section
     *
     * @param Newscoop\Entity\Ingest\Feed\Entry $entry
     * @return int
     */
    private function getSection(Entry $entry)
    {
        switch ($entry->getSubject()) {
            case '15000000':
                return $this->config['section_sport'];
                break;

            case '1000000':
                return $this->config['section_culture'];
                break;
        }

        if ($entry->getCountry() != 'CH')  {
            return $this->config['section_international'];
        }

        if ($entry->getProduct() == "Regionaldienst Nord") {
            return $this->config['section_basel'];
        }

        return $this->config['section_other'];
    }

    /**
     * Set article data
     *
     * @param Article $article
     * @param Newscoop\Entity\Ingest\Feed\Entry $entry
     * @return void
     */
    private function setArticleData(\Article $article, Entry $entry)
    {
        $data = $article->getArticleData();
        $data->setProperty($this->config['field_summary'], $entry->getSummary());
        $data->setProperty($this->config['field_content'], $entry->getContent());
    }

    /**
     * Set article dates
     *
     * @param Article $article
     * @param Newscoop\Entity\Ingest\Feed\Entry $entry
     * @return void
     */
    private function setArticleDates(\Article $article, Entry $entry)
    {
        $entry->setPublished(new \DateTime());
        $article->setCreationDate($entry->getCreated()->format(self::DATETIME_FORMAT));
        $article->setPublishDate($entry->getPublished()->format(self::DATETIME_FORMAT));
        $article->setProperty('time_updated', $entry->getUpdated()->format(self::DATETIME_FORMAT));
    }
}