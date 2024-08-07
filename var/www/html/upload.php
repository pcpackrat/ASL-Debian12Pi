<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // File upload handling
    $configFile = $_FILES["configFile"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $addToConf = isset($_POST["addToConf"]) && $_POST["addToConf"] === "yes"; // Check the checkbox

    // Validate and move the config file
    $uploadDir = "/etc/openvpn/client/";
    $targetFilePath = $uploadDir . $configFile["name"];

    // Explicitly set MIME type to "text/plain"
    $configFile["type"] = "text/plain";

    if ($configFile["error"] == 0) {
        if ($configFile["type"] == "text/plain") {
            if (move_uploaded_file($configFile["tmp_name"], $targetFilePath)) {
                // Read the content of the config file
                $configContent = file_get_contents($targetFilePath);

                // Modify the necessary line
                $configContent = preg_replace('/\bauth-user-pass\b/', 'auth-user-pass creds', $configContent);

                // Write the modified content back to the file
                file_put_contents($targetFilePath, $configContent);

                // Rename the uploaded file if needed
                $newFileName = "client.conf";
                $newFilePath = $uploadDir . $newFileName;

                if (rename($targetFilePath, $newFilePath)) {
                    echo "OpenVPN config file uploaded and saved successfully.<br>";
                } else {
                    echo "Error renaming the uploaded file.<br>";
                }

                // Append line after "explicit-exit-notify" if checkbox is checked
                if ($addToConf) {
                    // Read the content of the file again
                    $configContent = file_get_contents($newFilePath);

                    // Find the position of "explicit-exit-notify" and insert the new line after it
                    $searchString = "explicit-exit-notify";
                    $lineToAdd = "--pull-filter ignore redirect-gateway\n"; // Modify this line as needed
                    $position = strpos($configContent, $searchString);

                    if ($position !== false) {
                        // Find the end of the line containing the search string
                        $endOfLinePosition = strpos($configContent, "\n", $position);
                        if ($endOfLinePosition === false) {
                            $endOfLinePosition = strlen($configContent);
                        }

                        // Insert the new line after the found line
                        $newConfigContent = substr($configContent, 0, $endOfLinePosition + 1) . $lineToAdd . substr($configContent, $endOfLinePosition + 1);

                        // Write the modified content back to the file
                        file_put_contents($newFilePath, $newConfigContent);

                        echo "client.conf edited to not send all traffic through VPN.<br>";
                    } else {
                        echo "'explicit-exit-notify' not found in client.conf.<br>";
                    }
                }
            } else {
                echo "Error moving the uploaded file to the target directory.<br>";
            }
        } else {
            echo "Invalid file type. Only text/plain files are allowed.<br>";
        }
    } else {
        echo "Error uploading OpenVPN config file. Error code: {$configFile['error']}<br>";
    }

    // Save username and password to a text file
    $credsFilePath = $uploadDir . "creds";
    $credsFile = fopen($credsFilePath, "w");

    // Check if the file was opened successfully
    if ($credsFile === false) {
        echo "Error opening creds file for writing.<br>";
    } else {
        // Write username and password to the file
        if (fwrite($credsFile, $username . "\n" . $password . "\n") !== false) {
            echo "Username and password saved successfully.";
        } else {
            echo "Error writing to creds file.<br>";
        }

        // Close the file handle
        fclose($credsFile);
    }
} else {
    echo "Invalid request method.";
}
?>
