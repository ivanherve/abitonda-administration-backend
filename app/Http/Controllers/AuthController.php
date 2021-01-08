<?php

namespace App\Http\Controllers;

use App\Models\Token;
use Carbon\Traits\Date;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Check the email and the password hashed to the database.
     * If these elements do not exist, the function shows an error message.
     * If they exist, the function will create a new token for the authenticated user if this one does not have a token yet.
     * If the user already has a token, the function will create another token to him/her or
     * it will update the database to insert a token id, the user id and a new token.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signIn(Request $request)
    {
        $allOldTokens = Token::all();
        foreach ($allOldTokens as $k => $t) {
            # delete all of them
            if (date("Y-m-d H:i", strtotime("+1 day", strtotime($t->updated_at))) <= date("Y-m-d H:i"))
                if (strlen($t->Api_token) > 0) {
                    DB::select("UPDATE token SET Api_token = ? WHERE TokenId = ?", [null, $t->TokenId]);
                    Log::info("$t->TokenId deleted");
                }
        }

        $pswd = $request->input('password');
        $email = $request->input('email');
        //return $this->errorRes([$email, $pswd],404);
        $user = DB::select("call log_in(?,?);", [$email, $pswd]);
        if (!$user) return $this->errorRes(["L'adresse ou le mot de passe rentré n'est pas correcte", $user, $email, $pswd], 401);
        //return $user[0]->UserId;
        $userId = $user[0]->UserId;
        $hasNotToken = Token::all()->where('UserId', '=', $userId)->where('Api_token', '=', null)->first();
        $tokenId = Token::all()->where('UserId', '=', $userId)->where('Api_token', '=', null)->pluck('TokenId')->first();
        //return $this->errorRes([$tokenId, $hasNotToken], 404);
        if ($hasNotToken) {
            $token = Str::random(60);
            $newToken = DB::update("call update_token(?,?,?)", [$token, $tokenId, $userId]);
            $newToken = Token::all()->where('UserId', '=', $userId)->where('Api_token', '=', $token)->first();
            return $this->successRes(['user' => $user[0], 'token' => $newToken]);
        } else {
            $newToken = Token::create([
                'UserId' => $userId,
                'Api_token' => Str::random(60)
            ]);
        }
        $user = $user[0];
        if ($user->ProfilId < 2) return $this->errorRes(["Monsieur/Madame $user->Surname, votre compte n'est pas encore activé, veuillez envoyer un e-mail à emabitonda@gmail.com"], 401);
        return $this->successRes(['user' => $user, 'token' => $newToken]);
    }

    /**
     * Log out an authenticated user.
     * It get the token in use and update the database to set this token to null.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signOut(Request $request)
    {
        $Api_token = $request->header('Authorization');
        $token = Token::all()->where('Api_token', '=', $Api_token)->first();
        $tokenId = Token::all()->where('Api_token', '=', $Api_token)->pluck('TokenId')->first();
        if (!$token) {
            return $this->errorRes('Vous n\'êtes pas connecté', 401);
        }

        $token = DB::select("UPDATE token SET Api_token = ? WHERE TokenId = ?", [null, $tokenId]);
        $emptyTok = Token::all()->where('TokenId', '=', $tokenId)->pluck('Api_token')->first();

        if ($emptyTok == null) {
            return $this->successRes('Vous êtes maintenant déconnécté');
        }

        return $this->errorRes('Vous n\'êtes pas connecté', 401);
    }
}
