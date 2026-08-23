<?php
require_once 'cardBuilder.php';

class CardGridBuilder {
    private $cardsData;

    public function __construct(array $cardsData) {
        $this->cardsData = $cardsData;
    }

    public function render() {
        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">';
        
        foreach ($this->cardsData as $data) {
            echo '<div class="col">';
            $card = new CardBuilder(
                $data['image'] ?? '', 
                $data['title'] ?? '', 
                $data['text'] ?? ''
            );
            $card->render();
            echo '</div>';
        }
        
        echo '</div>';
    }
}
