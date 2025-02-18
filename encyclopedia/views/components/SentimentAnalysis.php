<?php
class SentimentAnalysis {
    private $data;

    public function __construct($jsonData = null) {
        $this->data = $jsonData ? json_decode($jsonData, true) : null;
    }

    public function parseFromXML($xml) {
        // Extract XML tags and values
        $matches = [];
        preg_match_all('/<(\w+)>([^<]+)<\/\w+>/', $xml, $matches, PREG_SET_ORDER);

        $data = [];
        foreach ($matches as $match) {
            $tag = $match[1];
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
            return sprintf('rgb(%d, %d, %d)', 0, $intensity, 0);
        } else {
            $intensity = min(255, round((-$score / 10) * 255));
            return sprintf('rgb(%d, %d, %d)', $intensity, 0, 0);
        }
    }

    private function getScoreWidth($score) {
        // Convert -10 to +10 scale to 0-100% width
        return min(100, abs($score * 10)) . '%';
    }

    public function render() {
        if (!$this->data) {
            return '<div class="sentiment-analysis">No sentiment data available</div>';
        }

        $html = '<div class="sentiment-analysis">';

        foreach ($this->data as $topic => $score) {
            if ($topic === 'sentiment') continue; // Skip overall sentiment for now

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

        // Add overall sentiment at the top
        if (isset($this->data['sentiment'])) {
            $overallScore = $this->data['sentiment'];
            $overallColor = $this->getScoreColor($overallScore);
            $overallWidth = $this->getScoreWidth($overallScore);

            $html = sprintf(
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
            ) . $html;
        }

        $html .= '</div>';

        return $html . $this->getStyles();
    }

    private function getStyles() {
        return '<style>
            .sentiment-analysis-wrapper {
                margin: 10px 0;
                padding: 10px;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                background: white;
            }
            .sentiment-analysis-wrapper h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #333;
            }
            .sentiment-analysis {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 10px 0;
            }
            .sentiment-overall {
                padding: 10px;
                margin-bottom: 15px;
                background: #f5f5f5;
                border-radius: 4px;
            }
            .sentiment-item {
                margin: 8px 0;
            }
            .sentiment-label {
                font-size: 14px;
                margin-bottom: 4px;
            }
            .sentiment-bar-container {
                display: flex;
                align-items: center;
                background: #f0f0f0;
                height: 24px;
                border-radius: 12px;
                overflow: hidden;
            }
            .sentiment-bar {
                height: 100%;
                transition: width 0.3s ease;
                border-radius: 12px;
            }
            .sentiment-score {
                position: absolute;
                margin-left: 10px;
                color: #333;
                font-weight: bold;
                font-size: 12px;
            }
        </style>';
    }
}
?>