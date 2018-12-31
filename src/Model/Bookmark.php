<?php

namespace App\Model;

use Origin\Model\Entity;

class Bookmark extends AppModel
{
    public $recursive = 1;

    public $validationRules = [
      'user_id' => ['rule' => 'notBlank', 'required' => true, 'on' => 'create'],
      'title' => ['rule' => 'notBlank'],
      'url' => ['rule' => 'url'],
    ];

    /**
     * A list of Categories for dropdown select.
     *
     * @var array
     */
    public $categories = [
      'Business' => 'Business',
      'Computing' => 'Computing',
      'Entertainment' => 'Entertainment',
      'Finance' => 'Finance',
      'Health' => 'Health',
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->validate($this->validationRules);
        $this->belongsTo('User');
        $this->hasAndBelongsToMany('Tag');
    }

    public function afterFind($results)
    {
        /*
         * Convert hasAndBelongsToMany tags into string
         */
        if (isset($results->tags)) {
            $results->tag_string = $this->tagsToString($results->tags);
        }

        return $results;
    }

    /**
     * Take the comma seperated string and covert to array of Tags.
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->hasProperty('tag_string')) {
            $entity->tags = array();
            $tags = explode(',', $entity->tag_string);

            foreach ($tags as $tag) {
                $entity->tags[] = $this->Tag->newEntity(['title' => $tag]);
            }
        }

        return true;
    }

    /**
     * Takes related records and converts to string.
     *
     * @param array $tags
     */
    protected function tagsToString(array $tags)
    {
        $result = [];
        foreach ($tags as $tag) {
            $result[] = $tag->title;
        }

        return implode(',', $result);
    }
}
