<?php

namespace App\Models;

use Hash;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'secret_password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
        $this->attributes['secret_password'] = Hash::make($value);
    }

    public function all_agents_selectpicker()
    {	
    	$agents = self::where(['type' => 'agent', 'is_active' => 1])->orderBy('id', 'asc')->get();

        $ahentes = array();
        $ahentes[] = array('' => 'select a type');
        foreach ($agents as $agent) {
            $ahentes[] = array(
                $agent->id => $agent->name
            );
        }

        $agents = array();
        foreach($ahentes as $ahente) {
            foreach($ahente as $key => $val) {
                $agents[$key] = $val;
            }
        }

        return $agents;
    }

    public function all_users_selectpicker()
    {	
    	$agents = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $ahentes = array();
        $ahentes[] = array('' => 'select a user');
        foreach ($agents as $agent) {
            $ahentes[] = array(
                $agent->id => $agent->name
            );
        }

        $agents = array();
        foreach($ahentes as $ahente) {
            foreach($ahente as $key => $val) {
                $agents[$key] = $val;
            }
        }

        return $agents;
    }
}
