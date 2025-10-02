<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class AIController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function query(Request $request)
    {
        $question = $request->input('question');

        if (!$question) {
            return response()->json(['error' => 'Missing question'], 400);
        }

        /**
         * 1. Récupérer le schéma de la DB
         */
        $tables = DB::select("
            SELECT TABLE_NAME 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");

        $schema = [];
        foreach ($tables as $table) {
            $columns = DB::select("
                SELECT COLUMN_NAME, DATA_TYPE 
                FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table->TABLE_NAME]);

            $schema[$table->TABLE_NAME] = $columns;
        }

        // Transformer en texte lisible pour l'IA
        $schemaText = "";
        foreach ($schema as $table => $columns) {
            $schemaText .= "Table: $table\n";
            foreach ($columns as $col) {
                $schemaText .= "- " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ")\n";
            }
            $schemaText .= "\n";
        }

        /**
         * 2. Construire le prompt
         */
        $systemPrompt = "Tu es un assistant SQL. 
Génère uniquement des requêtes SQL valides pour MySQL, sans commentaires, sans explications, sans backticks, sans ```sql.
Ne renvoie que la requête SQL brute.
Voici la structure de la base de données :\n\n$schemaText";

        /**
         * 3. Appeler OpenAI
         */
        $client = new Client();
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'verify' => false, // ⚠️ en prod, configure cacert.pem au lieu de false
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $question
                    ]
                ]
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        $sql = $body['choices'][0]['message']['content'] ?? null;

        if (!$sql) {
            return response()->json(['error' => 'Impossible de générer la requête SQL'], 500);
        }

        /**
         * 4. Nettoyer la requête
         */
        $sql = preg_replace('/```(sql)?/i', '', $sql);
        $sql = trim($sql);

        /**
         * 5. Vérifier la sécurité & validité
         */
        if (preg_match('/\b(DELETE|DROP|UPDATE|INSERT|ALTER|TRUNCATE)\b/i', $sql)) {
            return response()->json(['error' => 'Requête SQL non autorisée'], 403);
        }

        // Vérifier que les tables utilisées existent
        $validTables = array_keys($schema);
        $tablesFound = [];
        foreach ($validTables as $t) {
            if (stripos($sql, $t) !== false) {
                $tablesFound[] = $t;
            }
        }

        if (empty($tablesFound)) {
            return response()->json(['error' => 'La requête utilise une table inconnue', 'sql' => $sql], 400);
        }

        /**
         * 6. Exécuter la requête
         */
        try {
            $results = DB::select(DB::raw($sql));
        } catch (\Exception $e) {
            return response()->json([
                'question' => $question,
                'error' => 'Erreur SQL',
                'details' => $e->getMessage(),
                'sql' => $sql
            ], 500);
        }

        /**
         * 7. Retourner le résultat
         */
        return response()->json([
            'question' => $question,
            'sql' => $sql,
            'results' => $results
        ]);
    }
}
