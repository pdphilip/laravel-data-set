<?php

use PDPhilip\DataSet\DataModel;
use PDPhilip\DataSet\DataSet;

it('should create models', function () {
    $dataSet = new DataSet;
    $model = $dataSet->create();
    $model->name = 'Alpha';
    $model->save();

    expect($model)->toBeInstanceOf(DataModel::class);

});
