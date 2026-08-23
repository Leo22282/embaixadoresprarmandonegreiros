<?php
class CardBuilder {
    private $image;
    private $title;
    private $text;

    public function __construct(string $image, string $title, string $text) {
        $this->image = $image;
        $this->title = $title;
        $this->text = $text;
    }

    public function render() {
        echo '<div class="card h-100 shadow-sm">';
        $this->renderImage();
        echo '<div class="card-body">';
        $this->renderContent();
        echo '</div>';
        echo '</div>';
    }

    private function renderImage() {
        if (!empty($this->image)) {
            echo '<img src="' . htmlspecialchars($this->image) . '" alt="' . htmlspecialchars($this->title) . '" class="card-img-top" style="height: 200px; object-fit: cover;">';
        }
    }

    private function renderContent() {
        echo '<h5 class="card-title">' . htmlspecialchars($this->title) . '</h5>';
        echo '<p class="card-text">' . htmlspecialchars($this->text) . '</p>';
    }
}
