<?php

namespace Tests\Feature\Scoring;

use App\Facades\GradeScoring;
use App\Models\ScoringResultMini;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MiniScoringTest extends TestCase
{

    private int $buyerID;
    private int $scoringResultID;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyerID = 287254;
        $this->scoringResultID = 316;
    }

    /**
     * Отправка данных ML сервису по мини скорингу.
     *
     * @return void
     */
    public function test_send_request_to_ml_service()
    {
        $domain = config('test.ml.v2.url');

        Http::fake([
            $domain . 'scoring/base_limit/' => Http::response([
                'message' => 'Base scoring starts'
            ]),
        ]);

        GradeScoring::initMiniScoring($this->buyerID);

        $scoringResultMini = ScoringResultMini::query()->latest('created_at')->first();

        $this->scoringResultID = $scoringResultMini->id;

        $this->assertEquals($scoringResultMini->user_id, $this->buyerID);

    }

    /**
     * Получение ответа от ML сервиса по мини скорингу.
     *
     * @return void
     */
    public function test_get_response_from_ml_service()
    {

        $token = config('test.ml.token');

        $buyerID = $this->buyerID;

        $scoringResultID = $this->scoringResultID;

        $response = $this->post("http://" . env('APP_URL') . "/api/scoring/buyer/$buyerID/mini",
            [
                'data' => [
                    'scoring_request_id' => $scoringResultID,
                    'name' => 'Test',
                    'surname' => 'Test',
                    'patronymic' => 'Test',
                    'gender' => 1,
                    'birth_date' => '2000-01-01',
                    'issue_doc_date' => '2020-01-01',
                    'claim_id' => '12345',
                    'approved' => 1,
                ]
            ],
            ["Authorization" => "Bearer {$token}"]);

        $response->assertStatus(201);

    }
}
