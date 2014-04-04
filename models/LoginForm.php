<?php
/**
 * @copyright Copyright &copy; communityii, 2014
 * @package yii2-user
 * @version 1.0.0
 * @see https://github.com/communityii/yii2-user
 */

namespace communityii\user\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use communityii\user\Module;

/**
 * Model for the login form
 *
 * @property string $loginId
 * @property string $password
 * @property string $rememberMe
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class LoginForm extends Model
{
    public $loginId;
    public $password;
    public $rememberMe = true;

    private $_user = false;
    private $_settings = [];

    public function init()
    {
        $module = null;
        Module::validateConfig($module);
        $this->_settings = $module->loginSettings;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            // loginId and password are both required
            [['loginId', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
        if ($this->_settings['loginType'] === Module::LOGIN_EMAIL) {
            $rules += ['loginId', 'email'];
        }
        return $rules;
    }

    /**
     * Attribute labels for the User model
     *
     * @return array
     */
    public function attributeLabels()
    {
        if ($this->_settings['loginType'] === Module::LOGIN_USERNAME) {
            $userLabel = Yii::t('user', 'Username');
        } elseif ($this->_settings['loginType'] === Module::LOGIN_EMAIL) {
            $userLabel = Yii::t('user', 'Email');
        } else {
            $userLabel = Yii::t('user', 'Username or Email');
        }
        return [
            'loginId' => $userLabel,
            'password' => Yii::t('user', 'Password'),
            'rememberMe' => Yii::t('user', 'Remember Me'),
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    public function validatePassword()
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            $outcome = ($user) ? $user->validatePassword($this->password) : null;
            if (!$user || !$outcome) {
                $this->addError('password', Yii::t('user', 'Invalid username or password.'));
            }
            if ($outcome !== null) {
                $user->checkFailedLogin($outcome);
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @param $user the user model
     * @return boolean whether the user is logged in successfully
     */
    public function login($user)
    {
        return Yii::$app->user->login($user, $this->rememberMe ? $this->_settings['rememberMeDuration'] : 0);
    }

    /**
     * Finds user by [[username]], [[email]], or both
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            if ($this->_settings['loginType'] === Module::LOGIN_USERNAME) {
                $this->_user = User::findByUsername($this->loginId);
            } elseif ($this->_settings['loginType'] === Module::LOGIN_EMAIL) {
                $this->_user = User::findByEmail($this->loginId);
            } else {
                $this->_user = User::findByUserOrEmail($this->loginId)->limit(1);
            }
        }

        return $this->_user;
    }
}
