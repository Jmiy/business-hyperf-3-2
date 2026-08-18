<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Utils;

use Hyperf\Collection\Arr;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Collection\data_set;

class PublicValidator
{
    /**
     * 获取需要查询的字段 https://learnku.com/docs/laravel/7.x/validation/7467.
     * @param mixed $data
     * @param mixed $rules
     * @param mixed $messages
     * @param mixed $type
     * @return string app列表需要的字段
     */
    public static function handle($data, $rules = [], $messages = [], $type = '')
    {
        /**
         * 自定义错误消息
         * $messages = [
         * 'required' => ':attribute is required.',
         * 'email' => ':attribute must be a valid email address.',
         * 'same' => 'The :attribute and :other must match.',
         * 'size' => 'The :attribute must be exactly :size.',
         * 'between' => 'The :attribute value :input is not between :min - :max.',
         * 'in' => 'The :attribute must be one of the following types: :values',
         * ];.
         */
        $publicRules = [];
        switch ($type) {
            case 'api':
                $publicRules = [
                    'store_id' => 'required',
                ];

                if (isset($data['customer_id'])) {
                    $publicRules['customer_id'] = 'bail|required';
                } else {
                    $publicRules['account'] = 'bail|required'; // |email
                }

                break;
            case 'admin':
                break;
            default:
                break;
        }

        $rules = Arr::collapse([$publicRules, $rules]);
        if (empty($rules)) {
            return true;
        }

        $validator = getValidatorFactory()->make($data, $rules); // $messages
        if ($messages) {
            $validator->setCustomMessages($messages); // 和直接在  $validator->make($validatorData, $rules, $messages);效果是一样的  setCustomMessages更加灵活
        }

        if (! $validator->fails()) {
            return true;
        }

        $errors = $validator->errors();
        //            //查看特定字段的第一个错误信息
        //            //$errors->first('email');
        //            //查看特定字段的所有错误消息
        //            foreach ($errors->get('email') as $message) {
        //                //
        //            }
        //
        //            //查看所有字段的所有错误消息
        //            foreach ($errors->all() as $message) {
        //                //
        //                var_dump($message);
        //            }
        //
        //            //判断特定字段是否含有错误消息
        //            if ($errors->has('email')) {
        //                //
        //            }

        foreach ($rules as $key => $value) {
            if ($errors->has($key)) {
                return Response::json([], 0, $errors->first($key));
            }
        }
    }

    public static function getAttributeName($storeId, $code)
    {
        return implode('_', [$code, $storeId]);
    }

    public static function handleValidatorRuleMsg($data, &$validation = [])
    {
        $storeIds = [0, 1, 2, 3, 5, 6, 7, 8, 9, 10];
        foreach ($data as $attribute => $value) {
            if (is_array($value)) {
                foreach ($value as $_key => $_value) {
                    if (strpos((string) $_key, 'default') === false) {
                        $__key = static::getAttributeName($_key, $attribute);
                        $custom = data_get($_value, 'custom', []);
                        foreach ($custom as $rule => $msg) {
                            data_set($validation, 'custom.' . $__key . '.' . $rule, $msg);
                        }

                        $attributeName = data_get($_value, 'attribute-name', null);
                        if ($attributeName) {
                            data_set($validation, 'attributes.' . $__key, data_get($_value, 'attribute-name', null));
                        }
                    }
                }

                $custom = data_get($value, 'default', []);
                foreach ($storeIds as $storeId) {
                    $__key = static::getAttributeName($storeId, $attribute);
                    foreach ($custom as $rule => $msg) {
                        data_set($validation, 'custom.' . $__key . '.' . $rule, $msg, false);
                    }
                }
            } else {
                foreach ($storeIds as $storeId) {
                    $__key = static::getAttributeName($storeId, $attribute);
                    $rule = 'api_code_msg';
                    data_set($validation, 'custom.' . $__key . '.' . $rule, $value, false);
                }
            }
        }
        return $validation;
    }
}
