<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Exception;

class ProductsController extends ConsoleController
{
    CONST CSV_FILE = 'products.csv';
    CONST CSV_SEPARATE = ';';
    CONST CSV_ROW_LENGTH = 50;
    CONST CSV_COLUMN = 2;

    /**
     * Сумма набора продуктов
     * @var
     */
    public $sum;

    public $file;

    private $products = [];
    private $prices = [];
    private $uniqueProduct = [];

    private $tempSum = 0;

    private $basket;
    private $basketSum;

    private $lastKey;

    private $options = [
        'sum',
        'file',
        'verbose',
    ];

    private $optionAliases = [
        's' => 'sum',
        'f' => 'file',
        'v' => 'verbose',
    ];

    /**
     * @inheritdoc
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            $this->options
        );
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function optionAliases()
    {
        return array_merge(
            parent::optionAliases(),
            $this->optionAliases
        );
    }

    /**
     * Консольная команда получения продуктов на определенную сумму
     * @throws Exception
     */
    public function actionIndex()
    {
        $file = ($this->file) ? '' : \Yii::getAlias('@root') . '/' . static::CSV_FILE;
        $i = 0;

        if (($handle = @fopen($file, "rb")) !== false) {

            try {

                while (($rowArray = fgetcsv($handle, static::CSV_ROW_LENGTH, static::CSV_SEPARATE)) !== false) {

                    if (count($rowArray) != static::CSV_COLUMN) {
                        throw new \Exception('Несоответствие данных ' . var_export($rowArray, true));
                    }

                    list($product, $price) = $rowArray;

                    // Проверка на превышение цены и уникальность продукта
                    if ($price > $this->sum || in_array($product, $this->uniqueProduct)) {
                        $i++;
                        continue;
                    }

                    // Добавляем продукт в массив уникальных продуктов
                    $this->uniqueProduct[] = $product;

                    // Складываем продукт и цену
                    $this->products[$i] = $product;
                    $this->prices[$i] = $price;

                    $this->basketSum += $price;

                    if ($this->basketSum > $this->sum) {

                        arsort($this->prices);

                        $this->writeln(print_r($this->prices, true));

                        $this->tempSum = 0;
                        $this->basket = [];
                        $this->lastKey = 0;

                        foreach ($this->prices as $key => $price) {
                            if ($this->tempSum < $this->sum) {
                                $this->addBasket($key);
                            } elseif ($this->tempSum > $this->sum) {
                                if (key($this->prices) != $key) {
                                    $this->removeLastProduct();
                                    $this->addBasket($key);
                                }
                            } elseif ($this->tempSum == $this->sum) {
                                $this->output();
                            }
                        }
                    }
                    $i++;
                }

                // Продукты кончились до того как привысить сумму
                if (empty($this->basket)) {
                    foreach ($this->products as $key => $product) {
                        $this->addBasket($key);
                    }
                }

                $this->output();
            } catch (\Exception $e) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
        } else {
            $this->writeln('Не могу открыть файл');
        }
    }

    /**
     * Вывывод окончательного варианта
     */
    private function output()
    {
        if ($this->tempSum > $this->sum) {
            $this->removeLastProduct();
        }

        array_map(function($item) {
            echo $item['name'] . ': ' . $item['price'] . " р.\n";
        }, $this->basket);
        echo sprintf("----------------\nИтого: %d р.\n", $this->tempSum);
        exit;
    }

    /**
     * Добавляем во временную корзину
     * @param $key
     */
    private function addBasket($key)
    {
        if (!isset($this->basket[$key])) {
            $this->basket[$key] = [
                'name' => $this->products[$key],
                'price' => $this->prices[$key]
            ];
            $this->tempSum += $this->prices[$key];
        }
        $this->lastKey = $key;

        $this->writeln('Добавляем: ' . $this->products[$key] . ' : ' . $this->prices[$key] . "\n");
    }

    /**
     * Удаляем последний добавленный элемент
     * из корзины и вычитаем из суммы
     */
    private function removeLastProduct() {
        $this->tempSum -= $this->prices[$this->lastKey];
        $item = end($this->basket);
        $this->writeln('Удаляем: ' . $item['name'] . ' : ' . $this->prices[$this->lastKey] . "\n");
        array_pop($this->basket);
    }
}
