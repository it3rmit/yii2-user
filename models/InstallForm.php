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
use kartik\password\StrengthValidator;
use communityii\user\Module;

/**
 * Installation Form model for the module
 *
 * @property string $access_code
 * @property string $username
 * @property string $password
 * @property string $password_confirm
 * @property string $email
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class InstallForm extends Model
{
    /**
     * @var string installation access code
     */
    public $access_code;

    /**
     * @var string the superuser name
     *
     */
    public $username;

    /**
     * @var string the superuser password
     */
    public $password;

    /**
     * @var string superuser password confirmation field
     */
    public $password_confirm;

    /**
     * @var string superuser email address
     *
     */
    public $email;

    /**
     * @var string the action for the install that will derive the scenario
     */
    public $action;

    private $_module;

    /**
     * Initialize InstallForm model
     */
    public function init()
    {
        Module::validateConfig($this->_module);
        parent::init();
    }

    /**
     * InstallForm model validation rules
     *
     * @return array
     */
    public function rules()
    {
        $config = $this->_module->registrationSettings;
        $rules = [
            [['access_code', 'username', 'password', 'email', 'password_confirm', 'action'], 'safe'],
            ['access_code', 'required', 'on' => [Module::UI_ACCESS]],
            ['access_code', 'checkAccess', 'on' => [Module::UI_ACCESS]],
            [['username', 'email'], 'filter', 'filter' => 'trim', 'on' => [Module::UI_INSTALL]],
            ['email', 'email', 'on' => [Module::UI_INSTALL]],
            [['username', 'password', 'password_confirm', 'email'], 'required', 'on' => [Module::UI_INSTALL]],
            ['username', 'match', 'pattern' => $config['userNamePattern'], 'message' => $config['userNameValidMsg'], 'on' => [Module::UI_INSTALL]],
            ['username', 'string'] + $config['userNameRules'] + ['on' => Module::UI_INSTALL],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'on' => [Module::UI_INSTALL]],
            [['password'], StrengthValidator::className()] +  $this->_module->passwordSettings['strengthRules'] + ['on' => [Module::UI_INSTALL]]
        ];
        if (in_array(Module::UI_INSTALL, $this->_module->passwordSettings['validateStrength'])) {
            $rules[] = [['password'], StrengthValidator::className()] +
                $this->_module->passwordSettings['strengthRules'] +
                ['on' => [Module::UI_INSTALL]];
        }
        return $rules;
    }

    /**
     * Checks the access_code against the `installAccessCode` in the module configuration.
     * This is the 'checkAccess' validator as declared in rules().
     */
    public function checkAccess()
    {
        if ($this->_module->installAccessCode !== $this->access_code) {
            $this->addError('access_code', Yii::t('user', 'The installation access code entered is incorrect.'));
        }
        $userComponent = Yii::$app->get('user');
        if (!$userComponent instanceof \communityii\user\components\User) {
            $this->addError('access_code', Yii::t('user', 'You have not setup a valid class for your user component in your application configuration file. ' .
                'The class must extend {classValid}. Class currently set: {classSet}.', [
                    'classValid' => '\communityii\user\components\User' ,
                    'classSet' => $userComponent::classname()
                ]
            ));
        }
    }

    /**
     * Attribute labels for the InstallForm model
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'access_code' => Yii::t('user', 'Installation Access Code'),
            'username' => Yii::t('user', 'Username'),
            'password' => Yii::t('user', 'Password'),
            'password_confirm' => Yii::t('user', 'Confirm Password'),
            'email' => Yii::t('user', 'Email'),
        ];
    }

    public function attributeHints() {
        return [
            'access_code' => Yii::t('user', 'Enter the installation access code as setup in your module configuration.'),
            'username' => Yii::t('user', 'Select an username for the superuser'),
            'password' => Yii::t('user', 'Select a password for the superuser'),
            'password_confirm' => Yii::t('user', 'Reconfirm the superuser password'),
            'email' => Yii::t('user', 'Enter a valid email for the superuser'),
        ];

    }
}
