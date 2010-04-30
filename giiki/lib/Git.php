<?php
class Git {
	private
		$git_repo,
		$git_bin,
		$git_author
	;
	/**
	 * @param string to git repository
	 * @param string to git binary
	 */
	function __construct ($git_repo, $git_bin) {
		$this->git_repo = $git_repo;
		$this->git_bin = $git_bin;
		chdir($this->git_repo);
	}

	function _cmd ($command, &$output = "") {
		$cmd = "{$this->git_bin} $command";
		$output = array();
		$result;

		if (substr($command, 0, 3) == 'com') {
			//die(var_dump($cmd));
		}

		//die(var_dump($cmd));
		exec($cmd.' 2>&1', $output, $result);

		// FIXME: The -1 is a hack to avoid 'commit' on an unchanged repo to
		// fail.
		if ($result != 0) {
			// FIXME: HTMLify these strings
			print "<h1>Error</h1>\n<pre>\n";
			print "$" . $gitCommand . "\n";
			print join("\n", $output) . "\n";
			//print "Error code: " . $result . "\n";
			print "</pre>";
			return false;
		}
		return true;
	}

	/**
	 * Initialize git repo
	 */
	function init () {
		$res = $this->_cmd('init');
		return $res;
	}

	function set_author ($user) {
		$this->git_author = sprintf('%s <%s>', $user->name, $user->email);
	}

	/**
	 * Add all files ready to commit
	 */
	function add_all () {
		return $this->_cmd('add .');
	}

	/**
	 * Add all files ready to commit
	 */
	function commit ($message="Content updated") {
		$cmd = 'commit -m"'.$message.'" -n';
		if ($this->git_author) {
			$cmd.= ' --author="'.$this->git_author.'"';
		}

		return $this->_cmd($cmd);
	}

	function add_all_commit ($message) {
		$this->add_all();
		return $this->commit($message);
	}

	function rm ($file) {
		return $this->_cmd('rm '.$file);
	}

	function show ($what) {
		$this->_cmd('show '.$what, $output);
		return implode("\n", $output);
	}

	function difftool ($what) {
		$this->_cmd('difftool '.$what, $output);
		return implode("\n", $output);
	}


	function get_history($file = "") {
		$output = array();
		// FIXME: Find a better way to find the files that changed than --name-only
		$this->_cmd("log --name-only --pretty=format:'%H>%T>%an>%ae>%aD>%s' -- $file", $output);
		$history = array();
		$historyItem = array();
		foreach ($output as $line) {
			$logEntry = explode(">", $line, 6);
			if (sizeof($logEntry) > 1) {
				// Populate history structure
				$historyItem = array(
						"author" => $logEntry[2],
						"email" => $logEntry[3],
						"linked-author" => (
								$logEntry[3] == "" ?
									$logEntry[2]
									: "<a href=\"mailto:$logEntry[3]\">$logEntry[2]</a>"),
						"date" => $logEntry[4],
						"message" => $logEntry[5],
						"commit" => $logEntry[0]
					);
				$grav = new Gravatar($historyItem['email']);
				$grav->size = 20;
				$historyItem['avatar'] = $grav->gravatarLink();
				$historyItem['diff'] = $this->show(' --unified=1 '.$historyItem['commit'].' '.$file);
			}
			else if (!isset($historyItem["page"])) {
				$historyItem["page"] = $line;
				$history[] = $historyItem;
			}
		}
		return $history;
	}

}