<?php

namespace Drupal\name\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form controller for adding a name format.
 */
class NameFormatAddForm extends NameFormatFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save format');
    return $actions;
  }

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Name format %label added.', ['%label' => $this->entity->label()]));
  }

}
