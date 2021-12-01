<?php

namespace Drupal\price_fix_riall\Plugin\Field\FieldFormatter;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Plugin implementation of the 'riall_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "riall_field_formatter",
 *   label = @Translation("Riall field formatter"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class RiallFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Constructs a new PricePlainFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'use_of_tumans' => TRUE,
      ] + parent::defaultSettings();
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['use_of_tumans'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use of tumans'),
      '#default_value' => $this->getSetting('use_of_tumans'),
    ];


    return $elements;
  }
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('use_of_tumans')) {
      $summary[] = $this->t('Use of tumans');
    }
    else {
      $summary[] = $this->t('Use of riall');
    }
    return $summary;
  }
  /**
   * Gets the formatting options for the currency formatter.
   *
   * @return array
   *   The formatting options.
   */
  protected function getFormattingOptions() {
    $options = (bool) $this->getSetting('use_of_tumans');

    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $options = $this->getFormattingOptions();
    $currency_codes = [];
    foreach ($items as $delta => $item) {
      $currency_codes[] = $item->currency_code;
    }
    $currencies = $this->currencyStorage->loadMultiple($currency_codes);

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'price_fix_riall',
        '#number' => $options ? $item->number/10 : $item->number,
        '#toman' => (bool) $options,
        '#currency' => $currencies[$item->currency_code],
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
    }

    return $elements;
  }
}
