<?php

namespace Drupal\name;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

class NameOptionsProvider {

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManger;

  /**
   * The module handler.
   *
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The term storage manager.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The vocab storage manager.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Contructs the service.
   *
   * @param EntityManagerInterface $entity_manager
   * @param ModuleHandlerInterface $module_handler
   */
  public function __construct(EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    $this->entityManger = $entity_manager;
    $this->moduleHandler = $module_handler;

    if ($this->entityManger && $this->moduleHandler->moduleExists('taxonomy')) {
      $this->termStorage = $this->entityManger->getStorage('taxonomy_term');
      $this->vocabularyStorage = $this->entityManger->getStorage('taxonomy_vocabulary');
    }
  }

  /**
   * Options for a name component.
   */
  public function getOptions(FieldDefinitionInterface $field, $component) {
    $fs = $field->getFieldStorageDefinition()->getSettings();
    $options = $fs[$component . '_options'];
    foreach ($options as $index => $opt) {
      if (preg_match('/^\[vocabulary:([0-9a-z\_]{1,})\]/', trim($opt), $matches)) {
        unset($options[$index]);
        if ($this->termStorage && $this->vocabularyStorage) {
          $vocabulary = $this->vocabularyStorage->load($matches[1]);
          if ($vocabulary) {
            $max_length = isset($fs['max_length'][$component]) ? $fs['max_length'][$component] : 255;
            foreach ($this->termStorage->loadTree($vocabulary->id()) as $term) {
              if (Unicode::strlen($term->name) <= $max_length) {
                $options[] = $term->name;
              }
            }
          }
        }
      }
    }

    // Options could come from multiple sources, filter duplicates.
    $options = array_unique($options);

    if (isset($fs['sort_options']) && !empty($fs['sort_options'][$component])) {
      natcasesort($options);
    }
    $default = FALSE;
    foreach ($options as $index => $opt) {
      if (strpos($opt, '--') === 0) {
        unset($options[$index]);
        $default = trim(Unicode::substr($opt, 2));
      }
    }
    $options = array_map('trim', $options);
    $options = array_combine($options, $options);
    if ($default !== FALSE) {
      $options = ['' => $default] + $options;
    }
    return $options;
  }

}
