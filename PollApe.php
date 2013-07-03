<?php

Class PollApe Extends WireData {

	protected $page = null;

	
	public function __construct($name) {
		$this->set('name', $name);
	}

	public function init() {
		$this->settingsDecoded = json_decode($this->settings);
		$this->optionsDecoded = json_decode($this->options);
	}

	public function render() {

		if ( ! isset($this->optionsDecoded)) $this->init();

		if ($this->userAlreadyVoted()) {
			return $this->renderResults();
		}

		if (wire('input')->post->{$this->name}) {
			$this->processVoting();
		}

		if (count($this->optionsDecoded) < 1) return "";

		$out  = '';
		$out .= "<div class='PollApe $this->class'><form method='post' action='./'>";
		if ($this->settingsDecoded->title) $out .= "<h2>{$this->settingsDecoded->title}</h2>";

		$out .= "<div class='PollApeOptions'>";
		foreach ($this->optionsDecoded as $key => $option) {
			$value = $key + 1;
			$out .= "<div class='PollApeOption'><input type='radio' name='{$this->name}' id='{$this->name}-$value' value='$value' /> <label for='{$this->name}-$value'>$option->title</label></div>";
		}
		$out .= "</div>";
		$out .= "<div class='PollApeSubmit'><input type='submit' value='". $this->_("Vote") ."' /></div>";
		$out .= "</form></div>";
		return $out;
	}

	public function renderResults() {

		$totalVotes = $this->getTotalVotes();

		$out  = '';
		$out .= "<div class='PollApe PollApeResults $this->class'>";
		if ($this->settingsDecoded->title) $out .= "<h2>{$this->settingsDecoded->title}</h2>";
		foreach ($this->optionsDecoded as $key => $option) {
			$percentage = ($option->votes / $totalVotes) * 100;
			$out .= "<div class='PollApeResultRow'><h3>$option->title</h3>";
			$out .= "<div class='percentage' style='width: {$percentage}%'><span>$option->votes</span></div></div>";
		}
		$out .= "<p class='PollApeTotalVotes'>" . sprintf($this->_("%d votes total"), $totalVotes) . "</p>";
		$out .= "</div>";

		return $out;	
	}

	public function getTotalVotes() {
		$total = 0;
		foreach ($this->optionsDecoded as $option) {
			$total = $total + $option->votes;
		}
		return $total;
	}

	public function processVoting() {

		

		$userAgentAndIP = $this->getUaString();
		$this->voters .= "\n" . $userAgentAndIP;

		$this->addVote();

		$this->page->of(false);
		$this->page->{$this->name} = $this;
		$this->page->save($this->name); //);
		wire('session')->redirect(wire('page')->url);

	}

	public function addVote() {

		$post = wire('input')->post;

		foreach ($this->optionsDecoded as $key => $option) {
			$chosenOne = $post->{$this->name};
			if ($key + 1 === (int) $post->{$this->name}) {
				$this->optionsDecoded[$key]->votes++;
			}
		}

		$this->options = json_encode($this->optionsDecoded);
	}

	public function userAlreadyVoted() {
		$currentUaString = $this->getUaString();
		foreach (explode("\n", $this->voters) as $uaString) {
			if ($uaString === $currentUaString) return true;
		}
		return false;
	}

	public function __toString() {
		$this->init();
		if (count($this->optionsDecoded) > 0) return $this->render();
		else return "";
	}

	public function getUaString() {
		return $_SERVER['HTTP_USER_AGENT'] . "+" . $_SERVER['REMOTE_ADDR'];
	}

	public function setPage(Page $page) {
		$this->page = $page; 
	}
}
