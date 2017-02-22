<?php
namespace helpers\yii2;

use \Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Feature - Implementa Feature Toggles para auxiliar no desenvolvimento. Segue alguma das
 * ideias fornecidas originalmente por Martin Fowler em seu blog.
 *
 * @package helpers\yii2
 */
class Feature
{
    /**
     * Obtém um array com todas as funcionalidades contidas no array `featureToggle` no aqruivo
     * `params.php` na aplicação que está sendo executada (dashboard, backend, frontend).
     *
     * @return mixed
     */
    public static function getAll()
    {
        return \Yii::$app->params['featureToggle'];
    }

    /**
     * Similar ao método `getAll()`, porém retorna apenas uma única feature.
     *
     * @param $feature
     * @return mixed
     */
    public static function getOne($feature)
    {
        $list = self::getAll();
        return $list[$feature];
    }

    /**
     * Verifica se uma funcionalidade do sistema está habilitada nos parâmetros
     * do sistema (array `params`). Retorna true em caso positivo e false para negativo.
     *
     * @param $feature - o nome da feature como consta no array `featureToggle` no array `params`
     * @param null $rules - um array similar ao utilizado no `featureToggle` que poderá
     * substituir seus valores para um teste rápido inline. Não altera o valor real do
     * array no arquivo de parâmetros, mas modifica seus valores apenas para checar se
     * a condição é verdadeira. Aceita apenas um array idêntico ao usado nos `params` para
     * cada feature.
     * @param bool $merge - caso a variável $rules seja populada com um array, o boolean $merge
     * permite que se decida se o novo array passado em $rules substituirá o array de
     * configurações originais (caso $merge seja passado como true) ou se ele apenas fará um
     * merge, mantendo os valores originais, mas substituindo-os ou acrescentando novos de
     * acordo com o array informado.
     *
     * @return bool
     */
    public static function isEnabled($feature, $rules = null, $merge = false)
    {
        $feature = self::getOne($feature);
        if ($rules !== null) {
            if ($merge) {
                $feature = ArrayHelper::merge($feature, $rules);
            } else {
                $feature = $rules;
            }
        }
        if ($feature === false || $feature === null) {
            return false;
        } elseif ($feature === true) {
            return true;
        } elseif (is_array($feature)) {
            // faz a verificação pelo role do usuário
            $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId());
            if (array_key_exists('roles', $feature) && is_array($feature['roles']) && !empty($feature['roles']) && !empty($roles)) {
                $isEnabled = false;
                foreach ($roles as $key => $value) {
                    if (in_array($key, $feature['roles'])) {
                        $isEnabled = true; break;
                    }
                }
                if ($isEnabled === false) {
                    return false;
                }
            }
            // faz a verificação pelo array de id's de usuários habilitados
            if (array_key_exists('users', $feature) && is_array($feature['users']) && !empty($feature['users']) && !in_array(Yii::$app->user->getId(), $feature['users'])) {
                return false;
            }
            // faz a verificação pela constante YII_ENV de acordo com o environment onde a aplicação está rodando (dev, prod, test)
            if (array_key_exists('env', $feature) && is_array($feature['env']) && !empty($feature['env']) && !in_array(YII_ENV, $feature['env'])) {
                return false;
            }
        }

        return true;
    }
}