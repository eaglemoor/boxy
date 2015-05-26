<?php
/**
 * Created by PhpStorm.
 * User: eaglemoor
 * Date: 13.04.15
 * Time: 18:27
 */

namespace yii\boxy;


class AuthController extends \yii\rest\Controller {
    use ControllerTrait;

    public function authExcept() {
        return ['auth'];
    }

    public $modelClass;

    public function init() {
        if ($this->modelClass === null) {
            $this->modelClass = \Yii::$app->user->identityClass;
        }

        parent::init();
    }

    /**
     * Залогинивание и получение token для работы в системе
     *
     * @param string $login
     * @param string $password
     *
     * @return array
     * @throws \yii\web\UnauthorizedHttpException
     */
    public function actionAuth() {
        $login = \Yii::$app->getRequest()->getBodyParam('login');
        $password = \Yii::$app->getRequest()->getBodyParam('password');

        if (empty($login) || empty($password)) {
            throw new \yii\web\UnauthorizedHttpException("Login or/and password is empty");
        }

        /** @var User $modelClass */
        $modelClass = $this->modelClass;
        $user = $modelClass::findByLogin($login);
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException("User with login, email or phone not found");
        }

        if (!$user->validatePassword($password)) {
            throw new \yii\web\UnauthorizedHttpException("Not valid password");
        }

        $token = AccessToken::generateForUser($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Выход и удаление токена
     *
     * @throws \yii\web\ServerErrorHttpException
     * @throws \yii\db\StaleObjectException
     */
    public function actionLogout() {
        $token = \Yii::$app->user->getIdentity()->accessToken;
        $accessToken = AccessToken::findOne(['id' => $token, 'user_uid' => \Yii::$app->user->getId()]);

        if ($accessToken->delete() === false) {
            throw new \yii\web\ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        \Yii::$app->getResponse()->setStatusCode(204);
    }
}