<?php
class SentimentAnalysis {
    private $data;

    public function __construct($jsonData = null) {
        if (is_string($jsonData)) {
            $this->data = json_decode($jsonData, true);
        } else {
            $this->data = $jsonData;
        }
    }

    public function parseFromXML($xml) {
        // Extract XML tags and values
        $matches = [];
        preg_match_all('/<(\w+)>([-\d.]+)<\/\w+>/', $xml, $matches, PREG_SET_ORDER);

        $data = [];
        foreach ($matches as $match) {
            $tag = strtolower($match[1]);  // Normalize to lowercase
            $value = floatval($match[2]);
            $data[$tag] = $value;
        }

        $this->data = $data;
        return json_encode($data);
    }

    private function getScoreColor($score) {
        // Convert -10 to +10 scale to RGB values
        if ($score > 0) {
            $intensity = min(255, round(($score / 10) * 255));
            return sprintf('rgb(%d, %d, %d)', 255 - $intensity, 255, 255 - $intensity);
        } else {
            $intensity = min(255, round((-$score / 10) * 255));
            return sprintf('rgb(%d, %d, %d)', 255, 255 - $intensity, 255 - $intensity);
        }
    }

    private function getScoreWidth($score) {
        // Convert -10 to +10 scale to 0-100% width
        return min(100, abs($score * 5) + 50) . '%';  // 50% at 0, 100% at ±10
    }

    public function render() {
        if (!$this->data) {
            return '<div class="sentiment-analysis">No sentiment data available</div>';
        }

        $html = '<div class="sentiment-analysis">';

        // Overall sentiment first
        if (isset($this->data['sentiment'])) {
            $overallScore = $this->data['sentiment'];
            $overallColor = $this->getScoreColor($overallScore);
            $overallWidth = $this->getScoreWidth($overallScore);

            $html .= sprintf(
                '<div class="sentiment-overall">
                    <div class="sentiment-label">Overall Sentiment</div>
                    <div class="sentiment-bar-container">
                        <div class="sentiment-bar" style="width: %s; background-color: %s;"></div>
                        <div class="sentiment-score">%+.1f</div>
                    </div>
                </div>',
                $overallWidth,
                $overallColor,
                $overallScore
            );
        }

        // Then other topics
        foreach ($this->data as $topic => $score) {
            if ($topic === 'sentiment') continue;

            $color = $this->getScoreColor($score);
            $width = $this->getScoreWidth($score);

            $html .= sprintf(
                '<div class="sentiment-item">
                    <div class="sentiment-label">%s</div>
                    <div class="sentiment-bar-container">
                        <div class="sentiment-bar" style="width: %s; background-color: %s;"></div>
                        <div class="sentiment-score">%+.1f</div>
                    </div>
                </div>',
                htmlspecialchars(str_replace('_', ' ', ucfirst($topic))),
                $width,
                $color,
                $score
            );
        }

        $html .= '</div>';

        return $html . $this->getStyles();
    }

    private function getStyles() {
        return '<style>
            .sentiment-analysis-wrapper {
                margin: 10px 0;
                padding: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                background: white;
            }
            .sentiment-analysis {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 10px 0;
            }
            .sentiment-overall {
                padding: 12px;
                margin-bottom: 15px;
                background: #f8f9fa;
                border-radius: 6px;
                border: 1px solid #e9ecef;
            }
            .sentiment-item {
                margin: 12px 0;
            }
            .sentiment-label {
                font-size: 14px;
                margin-bottom: 6px;
                color: #495057;
                font-weight: 500;
            }
            .sentiment-bar-container {
                position: relative;
                background: #f8f9fa;
                height: 24px;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e9ecef;
            }
            .sentiment-bar {
                height: 100%;
                transition: width 0.3s ease;
                border-radius: 12px;
            }
            .sentiment-score {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: #212529;
                font-weight: 600;
                font-size: 12px;
                text-shadow: 0 0 2px rgba(255,255,255,0.8);
            }
        </style>';
    }
}