<?php

namespace Curricula\Service;

/**
 * Service responsible for AI operations using AWS Bedrock
 */
class AIService
{
    /**
     * Config service
     *
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * AIService constructor
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Standardize a CV using AI
     *
     * @param string $text Raw CV text
     * @return string Standardized CV in Markdown format
     */
    public function standardizeCV(string $text): string
    {
        $sections = implode("\n", [
            "Personal Information",
            "Academic Education",
            "Professional Experience",
            "Skills",
            "Additional Information"
        ]);

        $prompt = "You are a CV standardization expert. Convert the provided CV text into a consistent Markdown format with these sections:\n" . $sections .
            "\n\nFollow these rules strictly:\n" .
            "- Use level 2 headers (##) for all section names\n" .
            "- Format education and experience in chronological order\n" .
            "- Use bullet points for skills and responsibilities\n" .
            "- Keep all original information but reorganize it into the standard format\n" .
            "- Ensure consistent formatting across all CVs";

        $userPrompt = [
            "role" => "user",
            "content" => mb_convert_encoding("Please standardize this CV text in Markdown format. Use the exact section names provided. Output in " . 
                $this->configService->getOutputLanguage() . " language:\n\n" . $text, 'UTF-8')
        ];
        
        return $this->invokeBedrock($prompt, [$userPrompt]);
    }

    /**
     * Analyze a standardized CV using AI
     *
     * @param string $standardizedCV Standardized CV text
     * @return array Analysis data including report and CSV data
     */
    public function analyzeCV(string $standardizedCV): array
    {
        $jobRequirements = $this->configService->getJobRequirements();

        // Build the prompt with detailed evaluation criteria in English
        $evaluationCriteriaPrompt = "\n\nEvaluate the candidate in " . $jobRequirements['output_language'] . " language using the following specific criteria:\n";

        foreach ($jobRequirements['evaluation_criteria'] as $criterionName => $criterionData) {
            $evaluationCriteriaPrompt .= sprintf(
                "\n%s (Weight: %.2f):\n%s\n",
                ucfirst(str_replace('_', ' ', $criterionName)),
                $criterionData['weight'],
                $criterionData['description']
            );
        }

        // Add instructions to calculate the weighted score in English
        $evaluationCriteriaPrompt .= "\nCalculate the final score (0-100) considering the weights of each criterion. The final score should be objective, but must have a subjective component, in what is your personal opinion regarding this criteria, correlated with the overall CV. ";
        $evaluationCriteriaPrompt .= "For each criterion, assign a score from 0 to 100 and multiply by its corresponding weight. ";
        $evaluationCriteriaPrompt .= "The sum of these weighted values will be the candidate's final score.";

        $systemPrompt = "Output your response ONLY as a valid JSON object with the following structure:\n" .
            "{\n" .
            "  \"report\": \"[Detailed Markdown analysis with these section names: " .
            "Strengths, " .
            "Concerns, " .
            "Overall Fit, " .
            "Score, " .
            "Sentiment, " .
            "Incomplete Info\",\n" .
            "  \"csvData\": {\n" .
            "    \"score\": [numerical score between 0-100],\n" .
            "    \"sentiment\": [short sentiment analysis],\n" .
            "    \"name\": [candidate name],\n" .
            "    \"email\": [candidate email],\n" .
            "    \"incomplete_info\": [incomplete or ambiguous information],\n" .
            "    \"education\": [highest education level],\n" .
            "    \"key_skills\": [comma-separated list of top 5 skills]\n" .
            "  }\n" .
            "}\n\n" .
            "IMPORTANT: Return ONLY valid JSON without any additional text before or after. Do not include \"```json\" or \"```\" markers. Your complete response must be parseable as JSON.\n\n
            You are an expert HR recruiter with deep technical knowledge. Analyze the CV considering:\n1. Technical expertise and its alignment with our needs\n2. Quality and relevance of professional experience\n3. Cultural fit indicators\n4. Educational background\n5. Evidence of soft skills\n\nProvide a structured analysis including:\n- Key strengths\n- Potential areas of concern\n- Overall fit for the position\n- Numerical score (0-100)\n- Sentiment analysis of the fit (Short text)\n- Incomplete or ambiguous information (clearly identify any criteria that could not be adequately evaluated due to missing, vague, or contradictory information in the CV)\n\n" .
            $evaluationCriteriaPrompt;

        $userPrompt = [
            "role" => "user",
            "content" => mb_convert_encoding("Job Description:\n" . $jobRequirements['job_description'] .
                "\n\nPosition: " . $jobRequirements['position'] .
                "\n\nCandidate CV:\n" . $standardizedCV .
                "\n\nOutput the analysis in " . $this->configService->getOutputLanguage() . " language and in valid JSON format.", 'UTF-8')
        ];

        return $this->invokeBedrock($systemPrompt, [$userPrompt], true);
    }

    /**
     * Invoke AWS Bedrock model
     *
     * @param string $systemPrompt System prompt
     * @param array $messages Messages
     * @param bool $outputJson Whether to output JSON
     * @return string|array Response text or parsed JSON
     */
    private function invokeBedrock(string $systemPrompt, array $messages, bool $outputJson = false): string|array
    {
        $bedrockClient = $this->configService->getBedrockClient();

        $result = $bedrockClient->invokeModel([
            'modelId' => $this->configService->getModelId(),
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode([
                'anthropic_version' => 'bedrock-2023-05-31',
                'messages' => $messages,
                'system' => mb_convert_encoding($systemPrompt, 'UTF-8'),
                'max_tokens' => 30000
            ])
        ]);

        $response = json_decode($result['body']->getContents(), true);
        $text = $response['content'][0]['text'];

        $text = $this->sanitizeJsonString($text);
        // For analyzeCV that needs to return JSON
        if ($outputJson) {
            // If the model returns a code block with JSON
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
                $jsonText = $matches[1];
                $decodedJson = json_decode($jsonText, true);
                // If the JSON is valid, return it
                if ($decodedJson !== null) {
                    return $decodedJson;
                }
            }
            // Try to decode the complete text as JSON
            $decodedJson = json_decode($text, true);
            if ($decodedJson !== null) {
                return $decodedJson;
            }

            // If it fails, return an error object with the model's response
            return [
                'error' => 'Model did not return valid JSON',
                'report' => $text,
                'csvData' => [
                    'score' => 'N/A',
                    'sentiment' => 'N/A',
                    'name' => 'N/A',
                    'email' => 'N/A',
                    'incomplete_info' => 'N/A',
                    'education' => 'N/A',
                    'key_skills' => 'N/A'
                ]
            ];
        }

        return $text;
    }

    /**
     * Sanitize JSON string removing / escaping control characters that break json_decode()
     *
     * @param string $json    Texto (supostamente) JSON
     * @return string         JSON seguro para json_decode()
     */
    private function sanitizeJsonString(string $json): string
    {
        // 1) Garante UTF-8 válido (descarta bytes inválidos)
        $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');

        $out      = '';
        $inString = false;
        $escaped  = false;

        $len = strlen($json);
        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];
            $code = ord($ch);

            if ($escaped) {
                // apos uma barra invertida copiamos e zeramos flag
                $out    .= $ch;
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {           // começo de escape
                $out    .= '\\';
                $escaped = true;
                continue;
            }

            if ($ch === '"') {            // abre ou fecha string
                $inString = !$inString;
                $out     .= '"';
                continue;
            }

            if ($inString && $code <= 0x1F) {   // controle bruto dentro de string
                switch ($code) {
                    case 0x08: $out .= '\b'; break;
                    case 0x0C: $out .= '\f'; break;
                    case 0x0A: $out .= '\n'; break;
                    case 0x0D: $out .= '\r'; break;
                    case 0x09: $out .= '\t'; break;
                    default:   $out .= sprintf('\u%04X', $code);
                }
            } else {
                $out .= $ch;               // byte normal
            }
        }
        return $out;
    }
}
