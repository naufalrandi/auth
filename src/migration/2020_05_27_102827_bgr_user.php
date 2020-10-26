<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Bageur\Auth\model\user;

class BgrUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bgr_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_level');
            $table->string('name');
            $table->string('username');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('foto')->nullable();
            $table->string('foto_path')->nullable();
            $table->json('addons')->nullable();
            $table->boolean('status')->default(1);
            $table->rememberToken();
            $table->timestamps();
        }); 

        $user = new user;
        $user->id               = 1;
        $user->id_level         = 1;
        $user->name             = 'Ginanjar Maulana';
        $user->username         = 'ginda';
        $user->email            = 'ginda@bageur.com';
        $user->password         = Hash::make('123123');
        $user->save();
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bgr_user');
    }
}
