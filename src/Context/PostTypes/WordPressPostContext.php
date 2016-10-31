<?php
namespace StephenHarris\WordPressBehatExtension\Context\PostTypes;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use StephenHarris\WordPressBehatExtension\Context\MetaData\MetaType;

/**
 * Defines steps related to posts and other post types
 *
 * @package StephenHarris\WordPressBehatExtension\Context
 */
class WordPressPostContext implements Context
{
    use \StephenHarris\WordPressBehatExtension\Context\PostTypes\WordPressPostTrait;

    /**
     * Add these posts to this wordpress installation
     * Example: Given there are posts
     *              | post_title      | post_content              | post_status | post_author | post_date           |
     *              | Just my article | The content of my article | publish     | 1           | 2016-10-11 08:30:00 |
     *
     *
     * @Given /^there are posts$/
     */
    public function thereArePosts(TableNode $table)
    {
        foreach ($table->getHash() as $postData) {
            $postData = $this->parseArgs($postData);
            $this->insert($postData);
        }
    }

    private function parseArgs($postData)
    {
        if (isset($postData['post_author'])) {
            $user = get_user_by('login', $postData['post_author']);
            if (! ( $user instanceof \WP_User )) {
                throw new \Exception(sprintf('User "%s" not found', $postData['post_author']));
            }
            $postData['post_author'] = (int) $user->ID;
        }
        return $postData;
    }

    /**
     * Example: Given the event "My event title" has event-category terms "family,sports"
     *
     * @Given /^the ([a-zA-z_-]+) "([^"]*)" has ([a-zA-z_-]+) terms ((?:[^,]+)(?:,\s*([^,]+))*)$/i
     */
    public function thePostTypeHasTerms($postType, $title, $taxonomy, $terms)
    {
        $post = $this->getPostByName($title, $postType);

        $names = array_map('trim', explode(',', $terms));
        $terms = array();
        foreach ($names as $name) {
            $term = get_term_by('name', htmlspecialchars($name), $taxonomy);
            if (! $term) {
                throw new \Exception(
                    sprintf('Could not find "%s" term %s', $taxonomy, $name)
                );
            }
            $terms[] = $term->slug;
        }
        $term_ids = wp_set_object_terms($post->ID, $terms, $taxonomy, false);

        $this->assignPostTypeTerms($post, $taxonomy, $term_ids);
    }

    /**
     * Add these posts to this wordpress installation
     * Example: Given the post "My post" has meta data
     *              | key   | value |
     *              | hello | world |
     *              | foo   | bar   |
     *              | foo   | baz   |
     *
     *
     * @Given /^the ([a-zA-z_-]+) "([^"]*)" has meta data$/i
     */
    public function thePostTypeHasMetaData($postType, $title, TableNode $table)
    {
        $post = $this->getPostByName($title, $postType);
        $postMeta = new MetaType(MetaType::POST);
        foreach ($table->getHash() as $metaData) {
            $postMeta->addMeta($post, $metaData['key'], $metaData['value']);
        }
    }

    /**
     * Example: Then the event "My event title" should have event-category terms "family,sports"
     * @Then /^the ([a-z0-9_\-]*) "([^"]*)" should have ([a-z0-9_\-]*) terms "([^"]*)"$/
     */
    public function thePostTypeShouldHaveTerms($postType, $title, $taxonomy, $terms)
    {
        $post = $this->getPostByName($title, $postType);
        $this->assertPostTypeTerms($post, $taxonomy, $terms);
    }
    
    /**
     * Example: Then the post "My post title" should have status "published"
     * @Then /^the ([a-z0-9_\-]*) "([^"]*)" should have status "([^"]*)"$/
     */
    public function thePostTypeShouldHaveStatus($postType, $title, $status)
    {
        $post = $this->getPostByName($title, $postType);
        $this->assertPostTypeStatus($post, $status);
    }
    /**
     * Example: Then the post "My post title" should have the value "%s" for the key "%s"
     * @Then /^the ([a-z0-9_\-]*) "([^"]*)" should have the value "([^"]*)" for the key "([^"]*)"$/
     */
    public function thePostTypeShouldHaveMetaKeyValue($postType, $title, $value, $key)
    {
        $post = $this->getPostByName($title, $postType);
        //TODO it might be better that we have a 'post meta data' layer between the context
        //and MetaType where we can ensure the cache is cleaned.
        clean_post_cache($post->ID);
        $postMeta = new MetaType(MetaType::POST);
        $postMeta->assertMetaKeyValue($post, $key, $value);
    }

    /**
     * Example: Then the post "My post title" should have the value "%s" for the key "%s"
     * @Then /^the ([a-z0-9_\-]*) "([^"]*)" should not have the value "([^"]*)" for the key "([^"]*)"$/
     */
    public function thePostTypeShouldNotHaveMetaKeyValue($postType, $title, $value, $key)
    {
        $post = $this->getPostByName($title, $postType);
        //TODO it might be better that we have a 'post meta data' layer between the context
        //and MetaType where we can ensure the cache is cleaned.
        clean_post_cache($post->ID);
        $postMeta = new MetaType(MetaType::POST);
        $postMeta->assertNotMetaKeyValue($post, $key, $value);
    }
}
