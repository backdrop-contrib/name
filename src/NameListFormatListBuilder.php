<?php

namespace Drupal\name;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

class NameListFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatterInterface
   */
  protected $formatter;

  /**
   * The name format parser.
   *
   * @var \Drupal\name\NameFormatParser
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('name.formatter'),
      $container->get('name.format_parser')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\name\NameFormatterInterface $formatter
   *   The name formatter.
   * @param \Drupal\name\NameFormatParser $parser
   *   The name format parser.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, NameFormatterInterface $formatter, NameFormatParser $parser) {
    parent::__construct($entity_type, $storage);
    $this->parser = $parser;
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row = [];
    $row['label'] = $this->t('Label');
    $row['id'] = $this->t('Machine name');
    $row['settings'] = $this->t('Settings');
    $row['examples'] = $this->t('Examples');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\name\NameListFormatInterface $entity */
    $row = [];
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    $and_options = $this->formatter->getLastDelimitorTypes();
    $and_delimiter = isset($and_options[$entity->and]) ? $and_options[$entity->and] : $this->t('-- invalid option --');

    $and_behavior_options = $this->formatter->getLastDelimitorBehaviors(FALSE);
    $and_behavior = isset($and_behavior_options[$entity->delimiter_precedes_last]) ? $and_behavior_options[$entity->delimiter_precedes_last] : $this->t('-- invalid option --');
    if ($entity->el_al_min) {
      $behavior = $this->t('Reduce after @max items and show @min items followed by <em>el al</em>.', [
        '@max' => $entity->el_al_min,
        '@min' => min([$entity->el_al_min, $entity->el_al_first]),
      ]);
    }
    else {
      $behavior = $this->t('Show all names.');
    }
    $settings = [
      $behavior,
      $this->t('Delimiters: "%delimiter" and %last', [
        '%delimiter' => $entity->delimiter,
        '%last' => $and_delimiter,
      ]),
      $this->t('Last delimiter: %delimiter', ['%delimiter' => $and_behavior]),
    ];
    if ($entity->isLocked()) {
      $settings[] = t('Default format (locked)');
    }

    $row['settings'] = new FormattableMarkup(implode('<br>', $settings), []);

    // @todo: Add examples.
    $row['examples'] = array(
      'data' => array(
        '#markup' => '',
      )
    );
    $operations = $this->buildOperations($entity);
    $row['operations']['data'] = $operations;
    return $row;
  }

  /**
   * Provides some example lists based on various length name lists.
   */
  public function examples(EntityInterface $entity) {
    return 'todo';
    $examples = array();
    foreach ($this->nameExamples() as $index => $example_name) {
      $formatted = Html::escape($this->parser->parse($example_name, $entity->get('pattern')));
      if (empty($formatted)) {
        $formatted = '<em>&lt;&lt;empty&gt;&gt;</em>';
      }
      $examples[] = $formatted . " <sup>{$index}</sup>";
    }
    return $examples;
  }

  /**
   * Example names.
   *
   * @return null
   */
  public function nameExamples() {
    module_load_include('inc', 'name', 'name.admin');
    return name_example_names();
  }

}
