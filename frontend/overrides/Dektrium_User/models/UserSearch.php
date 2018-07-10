<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace overrides\Dektrium_User\models;

use dektrium\user\Finder;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form about User.
 */
class UserSearch extends Model
{
    /** @var string */
    public $username;

    /** @var string */
    public $email;

    /** @var integer */
    public $created_at;

    /** @var string */
    public $registration_ip;

    /** @var string */
    public $accountExpire;

    /** @var string */
    public $passwordExpire;

    /** @var string */
    public $lastvisit_at;

    /** @var string */
    public $confirmed_at;

    /** @var Finder */
    protected $finder;

    /**
     * @param Finder $finder
     * @param array $config
     */
    public function __construct(Finder $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($config);
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            'fieldsSafe' => [['username', 'email', 'registration_ip', 'created_at', 'accountExpire', 'passwordExpire', 'lastvisit_at', 'confirmed_at'], 'safe'],
            'createdDefault' => ['created_at', 'default', 'value' => null]
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'username'        => \Yii::t('user', 'Username'),
            'email'           => \Yii::t('user', 'Email'),
            'created_at'      => \Yii::t('user', 'Registration time'),
            'registration_ip' => \Yii::t('user', 'Registration ip'),
            'accountExpire' => "Acount Expire",
            'passwordExpire' => "Password Expire",
            'lastvisit_at' => "Last Visit At",
            'confirmed_at'=> "Confirmed At",
        ];
    }

    /**
         * @param $params
         * @return ActiveDataProvider
         */
        public function search($params)
        {
            $query = $this->finder->getUserQuery();

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);

            if (!($this->load($params) && $this->validate())) {
                return $dataProvider;
            }

            if ($this->created_at !== null) {
                $date = strtotime($this->created_at);
                $query->andFilterWhere(['between', 'created_at', $date, $date + 3600 * 24]);
            }

            $query->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'email', $this->email])
                ->andFilterWhere(['registration_ip' => $this->registration_ip]);

            return $dataProvider;
        }
}
