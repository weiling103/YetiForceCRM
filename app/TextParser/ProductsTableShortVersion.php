<?php

namespace App\TextParser;

/**
 * Products table short version class.
 *
 * @copyright YetiForce Sp. z o.o.
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Arkadiusz Sołek <a.solek@yetiforce.com>
 */
class ProductsTableShortVersion extends Base
{
	/** @var string Class name */
	public $name = 'LBL_PRODUCTS_TABLE_SHORT_VERSION';

	/** @var mixed Parser type */
	public $type = 'pdf';

	/**
	 * Process.
	 *
	 * @return string
	 */
	public function process()
	{
		$html = '';
		if (!$this->textParser->recordModel->getModule()->isInventory()) {
			return $html;
		}
		$inventory = \Vtiger_Inventory_Model::getInstance($this->textParser->moduleName);
		$fields = $inventory->getFieldsByBlocks();
		$baseCurrency = \Vtiger_Util_Helper::getBaseCurrency();
		$inventoryRows = $this->textParser->recordModel->getInventoryData();
		$firstRow = current($inventoryRows);
		if ($inventory->isField('currency')) {
			if (!empty($firstRow) && null !== $firstRow['currency']) {
				$currency = $firstRow['currency'];
			} else {
				$currency = $baseCurrency['id'];
			}
			$currencyData = \App\Fields\Currency::getById($currency);
			$currencySymbol = $currencyData['currency_symbol'];
		}
		if (!empty($fields[1])) {
			$fieldsColumnQuotes = ['Quantity', 'GrossPrice', 'Name', 'UnitPrice', 'TotalPrice'];
			$fieldsTextRight = ['Quantity', 'GrossPrice', 'UnitPrice', 'TotalPrice'];
			$fieldsWithCurrency = ['TotalPrice', 'GrossPrice', 'UnitPrice'];
			$html .= '<table class="products-table-short-version" style="width:100%;border-collapse:collapse;">
				<thead>
					<tr>';
			foreach ($fields[1] as $field) {
				if ($field->isVisible() && in_array($field->getType(), $fieldsColumnQuotes)) {
					$html .= '<th class="col-type-' . $field->getType() . '" style="padding:0px 4px;text-align:center;">' . \App\Language::translate($field->get('label'), $this->textParser->moduleName) . '</th>';
				}
			}
			$html .= '</tr></thead><tbody>';
			$counter = 0;
			foreach ($inventoryRows as $inventoryRow) {
				++$counter;
				$html .= '<tr class="row-' . $counter . '">';
				foreach ($fields[1] as $field) {
					if (!$field->isVisible() || !in_array($field->getType(), $fieldsColumnQuotes)) {
						continue;
					}
					if ('ItemNumber' === $field->getType()) {
						$html .= '<td class="col-type-ItemNumber" style="padding:0px 4px;border:1px solid #ddd;font-weight:bold;">' . $inventoryRow['seq'] . '</td>';
					} elseif ('ean' === $field->getColumnName()) {
						$code = $inventoryRow[$field->getColumnName()];
						$html .= '<td class="col-type-barcode"><div data-barcode="EAN13" data-code="' . $code . '" data-size="1" data-height="16"></div></td>';
					} else {
						$itemValue = $inventoryRow[$field->getColumnName()];
						$html .= '<td class="col-type-' . $field->getType() . '" style="border:1px solid #ddd;padding:0px 4px;' . (in_array($field->getType(), $fieldsTextRight) ? 'text-align:right;' : '') . '">';
						if ('Name' === $field->getType()) {
							$html .= '<strong>' . $field->getDisplayValue($itemValue, $inventoryRow) . '</strong>';
							foreach ($inventory->getFieldsByType('Comment') as $commentField) {
								if ($commentField->isVisible() && ($value = $inventoryRow[$commentField->getColumnName()])) {
									$comment = $commentField->getDisplayValue($value, $inventoryRow);
									if ($comment) {
										$html .= '<br />' . $comment;
									}
								}
							}
						} elseif (\in_array($field->getType(), $fieldsWithCurrency, true) && !empty($currencySymbol)) {
							$html .= $field->getDisplayValue($itemValue, $inventoryRow) . ' ' . $currencySymbol;
						} else {
							$html .= $field->getDisplayValue($itemValue, $inventoryRow);
						}
						$html .= '</td>';
					}
				}
				$html .= '</tr>';
			}
			$html .= '</tbody><tfoot><tr>';
			foreach ($fields[1] as $field) {
				if ($field->isVisible() && in_array($field->getType(), $fieldsColumnQuotes)) {
					$html .= '<th class="col-type-' . $field->getType() . '" style="padding:0px 4px;text-align:right;">';
					if ($field->isSummary()) {
						$sum = 0;
						foreach ($inventoryRows as $inventoryRow) {
							$sum += $inventoryRow[$field->getColumnName()];
						}
						if (!empty($currencySymbol)) {
							$html .= \CurrencyField::appendCurrencySymbol(\CurrencyField::convertToUserFormat($sum, null, true), $currencySymbol);
						} else {
							$html .= \CurrencyField::convertToUserFormat($sum, null, true);
						}
					}
					$html .= '</th>';
				}
			}
			$html .= '</tr></tfoot></table>';
		}
		return $html;
	}
}
