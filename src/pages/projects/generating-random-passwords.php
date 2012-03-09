<h1>Generating Random Passwords in PHP</h1>
<p>
    Generating unbiased random passwords is a surprisingly non-trivial problem. Most naive solutions, such as taking the remainder of a random integer or shuffling a string, lead to biases in the passwords. These biases and patterns make the passwords significantly easier to crack. We start by discussing a few common, but incorrect, ways of generating passwords, then provide a secure password generator class for PHP.
    
</p>

<h2>Common Mistakes</h2>

<h3>Weak Psudeo-Random Number Generator (PRNG)</h3>

<p>
    Psudeo-random number generators such as <a href="http://php.net/mt_rand">mt_rand</a> are not sufficient for generating passwords. They are designed so that their output looks statistically random, but they make no effort to be unpredictable. Given some of their output, it is easy to figure out their internal state, which can be used to predict their future output. An attacker who knew that a password was generated by a weak random number generator could theoretically devise an algorithm to guess passwords generated by its output faster than brute force. It's also possible that the PRNG could have a small internal state (32 bits, for example), which would allow an attacker to crack the password quicker by guessing the state of the PRNG rather than the password itself. It is never secure to generate a password with such a random number generator.
</p>
<p>
    Cryptographically secure random number generators (CSPRNG) must be used to generate passwords. CSPRNGs are unpredictable and incorporate randomness from the outside world (mouse movements, key presses, network packets) into their internal state. In PHP, the operating system's CSPRNG can be accessed with the <a href="http://php.net/mcrypt_create_iv">mcrypt_create_iv</a> function.
</p>

<h3>Modulo Character Set Size</h3>

<p>The following seems to be the most common naive solution:</p>

<div class="code badcode">
&lt;?php<br />
// THIS CODE IS INSECURE. DO NOT USE THIS CODE.<br />
$password = &quot;&quot;;<br />
$charset = &quot;abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&quot;;<br />
<br />
for($i = 0; $i &lt; 8; $i++)<br />
{<br />
&nbsp;&nbsp; &nbsp;$random_int = mt_rand();<br />
&nbsp;&nbsp; &nbsp;$password .= $charset[$random_int % strlen($charset)];<br />
}<br />
<br />
echo $password, &quot;\n&quot;;<br />
<br />
?&gt;<br />
</div>

<p>This code makes two mistakes. First, it uses a weak PRNG. Second, it uses a large random integer to select from a set of N elements by computing the remainder of the random integer divided by N. This isn't secure. Unless the number of possible random integers is a multiple of N, some characters will have a higher chance of being picked than others.

<p>
    For example, if number between 0 and 94 is generated by taking the remainder of a random byte divided by 95, the numbers 0 to 65 have a higher chance of being picked than the numbers 66 to 94. There are 3 possible byte values that will generate 0 to 65: N, N+95, and N+95+95. There are only two byte values that will generate 66 through 94: N and N+95 (not N+95+95 because 66 + 95 + 95 = 256, which is more than one byte). The same applies when a larger random integer is used, but there is less bias. Still, don't use this method.
</p>

<h2>Unbiased Random Selection Using Binary Data</h2>
<p>
The only known way to make an unbiased random selection from a set of N elements, using random bits, is to repeatedly generate a random number between 0 and 2<sup>k</sup> - 1, where 2<sup>k</sup> is the smallest power of two greater than N, until the random number is between 0 and N - 1 so it can be used to select an element from the set (numbers that aren't in that range are discarded). It may seem wasteful or inefficient to throw away some of the random numbers, but good CSPRNGs are  fast, and only about half of the random numbers will be thrown away on average [1].
</p>

<p>The following PHP code uses the method mentioned above to generate random ASCII, alpha-numeric, and HEX passwords. It can also generate passwords from a custom character set. The code is explicitly placed into the public domain, so feel free to use it for any purpose whatsoever.</p>



<h2>Example Usage:</h2>

<div class="code">
require_once(&#039;PasswordGenerator.php&#039;);<br />
$ascii = PasswordGenerator::getASCIIPassword(64);<br />
$hex = PasswordGenerator::getHexPassword(64);<br />
$alpha = PasswordGenerator::getAlphaNumericPassword(64);<br />
$custom = PasswordGenerator::getCustomPassword(array(&#039;a&#039;, &#039;b&#039;), 64);<br />
echo $ascii, &quot;\n&quot;, $hex, &quot;\n&quot;, $alpha, &quot;\n&quot;, $custom, &quot;\n&quot;;<br />

</div>

<h3>Output</h3>

<div class="code">
kbH&#039;q\rO`F!trPC=E1v)~D7&lt;g@`Ise0QkSlI93Ne/2/IIKgiIKZWnLrh^_oo^&amp;b)<br />
6D574CFF661E146C530729F8D44B545BC3192D1439B4113132B2FC221AFF56A7<br />
ic4vQ2OXMsOzCmsMnhEHFRc6rp2oDBUDq9KZJnFXsHbKO8xh5Kcpp9T4ZHFPcVg7<br />
abbababbabbaabaaaaabbabbbbababaabbabbaaabbabababbaaabbaaababbbbb
</div>

<h2>PHP Source (<a href="/source/PasswordGenerator.php.txt">download</a>):</h2>

<div class="code" style="font-size: 8pt;">
&lt;?php<br />
/*<br />
&nbsp;* Unbiased random password generator.<br />
&nbsp;* This code is placed into the public domain by Defuse Cyber-Security.<br />
&nbsp;* WWW: https://defuse.ca/<br />
&nbsp;*/<br />
class PasswordGenerator<br />
{<br />
&nbsp;&nbsp; &nbsp;public static function getASCIIPassword($length)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$printable = &quot;!\&quot;#$%&amp;&#039;()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~&quot;;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return self::getCustomPassword(str_split($printable), $length);<br />
&nbsp;&nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp;public static function getAlphaNumericPassword($length)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$alphanum = &quot;ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789&quot;;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return self::getCustomPassword(str_split($alphanum), $length);<br />
&nbsp;&nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp;public static function getHexPassword($length)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$hex = &quot;0123456789ABCDEF&quot;;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return self::getCustomPassword(str_split($hex), $length);<br />
&nbsp;&nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp;/* <br />
&nbsp;&nbsp; &nbsp; * Create a random password composed of a custom character set.<br />
&nbsp;&nbsp; &nbsp; * $characterSet - An *array* of strings the password can be composed of.<br />
&nbsp;&nbsp; &nbsp; * $length - The number of random strings (in $characterSet) to include in the password.<br />
&nbsp;&nbsp; &nbsp; * Returns false on error (always check!).<br />
&nbsp;&nbsp; &nbsp; */<br />
&nbsp;&nbsp; &nbsp;public static function getCustomPassword($characterSet, $length)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;if($length &lt; 1 || !is_array($characterSet))<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;return false;<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$charSetLen = count($characterSet);<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;if($charSetLen == 0)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;return false;<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$random = self::getRandomInts($length * 2);<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$mask = self::getMinimalBitMask($charSetLen - 1); <br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$password = array();<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// To generate the password, we repeatedly try random integers and use the ones within the range<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// 0 to $charSetLen - 1 to select an index into the character set. This is the only known way to<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// make a truly unbiased random selection from a set using random binary data.<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// A poorly implemented or malicious RNG could cause an infinite loop, leading to a denial of service.<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// We need to guarantee termination, so $iterLimit holds the number of further iterations we will allow.<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;// It is extremely unlikely (about 2^-64) that more than $length*64 random ints are needed.<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$iterLimit = max($length, $length * 64); &nbsp; // If length is close to PHP_INT_MAX we don&#039;t want to overflow.<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$randIdx = 0;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;while(count($password) &lt; $length)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;if($randIdx &gt;= count($random))<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$random = self::getRandomInts(2*($length - count($password)));<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$randIdx = 0;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;// This is wasteful, but RNGs are fast and doing otherwise adds complexity and bias.<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$c = $random[$randIdx++] &amp; $mask;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;// Only use the random number if it is in range, otherwise try another (next iteration).<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;if($c &lt; $charSetLen)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$password[] = $characterSet[$c];<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;// Guarantee termination<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$iterLimit--;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;if($iterLimit &lt;= 0)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;return false;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return implode($password);<br />
&nbsp;&nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp;// Returns the smallest bit mask of all 1s such that ($toRepresent &amp; mask) = $toRepresent.<br />
&nbsp;&nbsp; &nbsp;// $toRepresent must be an integer greater than or equal to 1.<br />
&nbsp;&nbsp; &nbsp;private static function getMinimalBitMask($toRepresent)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;if($toRepresent &lt; 1)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;return false;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$mask = 0x1;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;while($mask &lt; $toRepresent)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$mask = ($mask &lt;&lt; 1) | 1;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return $mask;<br />
&nbsp;&nbsp; &nbsp;}<br />
<br />
&nbsp;&nbsp; &nbsp;// Returns an array of $numInts random integers between 0 and PHP_INT_MAX<br />
&nbsp;&nbsp; &nbsp;private static function getRandomInts($numInts)<br />
&nbsp;&nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$rawBinary = mcrypt_create_iv($numInts * PHP_INT_SIZE, MCRYPT_DEV_URANDOM);<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;$ints = array();<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;for($i = 0; $i &lt; $numInts; $i+=PHP_INT_SIZE)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$thisInt = 0;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;for($j = 0; $j &lt; PHP_INT_SIZE; $j++)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;{<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$thisInt = ($thisInt &lt;&lt; 8) | (ord($rawBinary[$i+$j]) &amp; 0xFF);<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;}<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;// Absolute value in two&#039;s compliment (with min int going to zero)<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$thisInt = $thisInt &amp; PHP_INT_MAX;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;$ints[] = $thisInt;<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;}<br />
&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;return $ints;<br />
&nbsp;&nbsp; &nbsp;}<br />
}<br />
?&gt;<br />

</div>

<h2>References</h2>

<ol>
    <li> Tadayoshi Kohno, Niels Ferguson, and Bruce Schneier. "9.7 Choosing Random Elements." Cryptography Engineering: Design Principles and Practical Applications. Indianapolis, IN: Wiley Pub., 2010. Print.</li>
</ol>
