<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Pagination Class
 */
class Pagination
{

    var $baseUrl = ''; // The page we are linking to
    var $prefix = ''; // A custom prefix added to the path.
    var $suffix = ''; // A custom suffix added to the path.

    var $totalRows = 0; // Total number of items (database results)
    var $perPage = 10; // Max number of items you want shown per page
    var $numLinks = 2; // Number of "digit" links to show before/after the currently viewed page
    var $curPage = 0; // The current page being viewed
    var $usePageNumbers = false; // Use page number for segment instead of offset
    var $firstLink = '&lsaquo; First';
    var $nextLink = '&gt;';
    var $prevLink = '&lt;';
    var $lastLink = 'Last &rsaquo;';
    //var $uri_segment		= 3;
    var $fullTagOpen = '';
    var $fullTagClose = '';
    var $firstTagOpen = '';
    var $firstTagClose = '&nbsp;';
    var $lastTagOpen = '&nbsp;';
    var $lastTagClose = '';
    var $firstUrl = ''; // Alternative URL for the First Page.
    var $curTagOpen = '&nbsp;<strong>';
    var $curTagClose = '</strong>';
    var $nextTagOpen = '&nbsp;';
    var $nextTagClose = '&nbsp;';
    var $prevTagOpen = '&nbsp;';
    var $prevTagClose = '';
    var $numTagOpen = '&nbsp;';
    var $numTagClose = '';
    var $pageQueryString = false;
    var $queryStringSegment = 'per_page';
    var $displayPages = true;
    var $anchorClass = '';

    /**
     * Constructor
     *
     * @param array $params initialization parameters
     */
    public function __construct($params = array())
    {
        if (count($params) > 0) {
            $this->initialize($params);
        }

        if ($this->anchorClass != '') {
            $this->anchorClass = 'class="'.$this->anchorClass.'" ';
        }

        Fly::log('debug', "Pagination Class Initialized");
    }

    /**
     * Initialize Preferences
     *
     * @param array $params initialization parameters
     * @return void
     */
    public function initialize($params = array())
    {
        if (count($params) > 0) {
            foreach ($params as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     * Generate the pagination links
     *
     * @return string
     */
    public function create()
    {
        // If our item count or per-page total is zero there is no need to continue.
        if ($this->totalRows == 0 || $this->perPage == 0) {
            return '';
        }

        // Calculate the total number of pages
        $num_pages = ceil($this->totalRows / $this->perPage);

        // Is there only one page? Hm... nothing more to do here then.
        if ($num_pages == 1) {
            return '';
        }

        // Set the base page index for starting page number
        if ($this->usePageNumbers) {
            $base_page = 1;
        } else {
            $base_page = 0;
        }

        // Determine the current page number.
        /*
		if (Fly::getConfig('enable_query_strings') === TRUE || $this->pageQueryString === TRUE) {
			if (Fly::app()->Request->get($this->queryStringSegment) != $base_page) {
				$this->curPage = Fly::app()->Request->get($this->queryStringSegment);

				// Prep the current page - no funny business!
				$this->curPage = (int) $this->curPage;
			}
		} else {
			if (Fly::app()->Uri->segment($this->uri_segment) != $base_page) {
				$this->curPage = Fly::app()->Uri->segment($this->uri_segment);

				// Prep the current page - no funny business!
				$this->curPage = (int) $this->curPage;
			}
		}*/

        $this->curPage = Fly::app()->Request->get($this->queryStringSegment);

        // Prep the current page - no funny business!
        $this->curPage = (int)$this->curPage;

        // Set current page to 1 if using page numbers instead of offset
        if ($this->usePageNumbers && $this->curPage == 0) {
            $this->curPage = $base_page;
        }

        $this->numLinks = (int)$this->numLinks;

        if ($this->numLinks < 1) {
            throw new FlyException(Fly::t('fly', 'Your number of links must be a positive number.'));
        }

        if (!is_numeric($this->curPage)) {
            $this->curPage = $base_page;
        }

        // Is the page number beyond the result range?
        // If so we show the last page
        if ($this->usePageNumbers) {
            if ($this->curPage > $num_pages) {
                $this->curPage = $num_pages;
            }
        } else {
            if ($this->curPage > $this->totalRows) {
                $this->curPage = ($num_pages - 1) * $this->perPage;
            }
        }

        $uri_page_number = $this->curPage;

        if (!$this->usePageNumbers) {
            $this->curPage = floor(($this->curPage / $this->perPage) + 1);
        }

        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with
        $start = (($this->curPage - $this->numLinks) > 0) ? $this->curPage - ($this->numLinks - 1) : 1;
        $end = (($this->curPage + $this->numLinks) < $num_pages) ? $this->curPage + $this->numLinks : $num_pages;

        // Is pagination being used over GET or POST?  If get, add a per_page query
        // string. If post, add a trailing slash to the base URL if needed
        if (Fly::app()->getConfig('enable_query_strings') === true || $this->pageQueryString === true) {
            $this->baseUrl = rtrim($this->baseUrl).'&amp;'.$this->queryStringSegment.'=';
        } else {
            $this->baseUrl = rtrim($this->baseUrl, '/').'/';
        }

        // And here we go...
        $output = '';

        // Render the "First" link
        if ($this->firstLink !== false && $this->curPage > ($this->numLinks + 1)) {
            $first_url = ($this->firstUrl == '') ? $this->baseUrl : $this->firstUrl;
            $output .= $this->firstTagOpen.'<a '.$this->anchorClass.'href="'.$first_url.'">'.$this->firstLink.'</a>'.$this->firstTagClose;
        }

        // Render the "previous" link
        if ($this->prevLink !== false AND $this->curPage != 1) {
            if ($this->usePageNumbers) {
                $i = $uri_page_number - 1;
            } else {
                $i = $uri_page_number - $this->perPage;
            }

            if ($i == 0 && $this->firstUrl != '') {
                $output .= $this->prevTagOpen.'<a '.$this->anchorClass.'href="'.$this->firstUrl.'">'.$this->prevLink.'</a>'.$this->prevTagClose;
            } else {
                $i = ($i == 0) ? '' : $this->prefix.$i.$this->suffix;
                $output .= $this->prevTagOpen.'<a '.$this->anchorClass.'href="'.$this->baseUrl.$i.'">'.$this->prevLink.'</a>'.$this->prevTagClose;
            }
        }

        // Render the pages
        if ($this->displayPages !== false) {
            // Write the digit links
            for ($loop = $start - 1; $loop <= $end; $loop++) {
                if ($this->usePageNumbers) {
                    $i = $loop;
                } else {
                    $i = ($loop * $this->perPage) - $this->perPage;
                }

                if ($i >= $base_page) {
                    if ($this->curPage == $loop) {
                        $output .= $this->curTagOpen.$loop.$this->curTagClose; // Current page
                    } else {
                        $n = ($i == $base_page) ? '' : $i;

                        if ($n == '' && $this->firstUrl != '') {
                            $output .= $this->numTagOpen.'<a '.$this->anchorClass.'href="'.$this->firstUrl.'">'.$loop.'</a>'.$this->numTagClose;
                        } else {
                            $n = ($n == '') ? '' : $this->prefix.$n.$this->suffix;

                            $output .= $this->numTagOpen.'<a '.$this->anchorClass.'href="'.$this->baseUrl.$n.'">'.$loop.'</a>'.$this->numTagClose;
                        }
                    }
                }
            }
        }

        // Render the "next" link
        if ($this->nextLink !== false AND $this->curPage < $num_pages) {
            if ($this->usePageNumbers) {
                $i = $this->curPage + 1;
            } else {
                $i = ($this->curPage * $this->perPage);
            }

            $output .= $this->nextTagOpen.'<a '.$this->anchorClass.'href="'.$this->baseUrl.$this->prefix.$i.$this->suffix.'">'.$this->nextLink.'</a>'.$this->nextTagClose;
        }

        // Render the "Last" link
        if ($this->lastLink !== false AND ($this->curPage + $this->numLinks) < $num_pages) {
            if ($this->usePageNumbers) {
                $i = $num_pages;
            } else {
                $i = (($num_pages * $this->perPage) - $this->perPage);
            }
            $output .= $this->lastTagOpen.'<a '.$this->anchorClass.'href="'.$this->baseUrl.$this->prefix.$i.$this->suffix.'">'.$this->lastLink.'</a>'.$this->lastTagClose;
        }

        // Kill double slashes.  Note: Sometimes we can end up with a double slash
        // in the penultimate link so we'll kill all double slashes.
        $output = preg_replace("#([^:])//+#", "\\1/", $output);

        // Add the wrapper HTML if exists
        $output = $this->fullTagOpen.$output.$this->fullTagClose;

        return $output;
    }
}