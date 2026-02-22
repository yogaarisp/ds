package com.keetech.dimsumpos;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.KeyEvent;
import android.view.View;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.widget.Toast;
import android.Manifest;
import android.content.pm.PackageManager;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import java.util.ArrayList;
import java.util.List;
import org.json.JSONArray;
import org.json.JSONObject;

import com.dantsu.escposprinter.EscPosPrinter;
import com.dantsu.escposprinter.connection.DeviceConnection;
import com.dantsu.escposprinter.connection.bluetooth.BluetoothConnection;
import com.dantsu.escposprinter.connection.bluetooth.BluetoothPrintersConnections;
import com.dantsu.escposprinter.connection.usb.UsbConnection;
import com.dantsu.escposprinter.connection.usb.UsbPrintersConnections;

public class MainActivity extends Activity {

    private WebView webView;
    private ProgressBar progressBar;
    private ValueCallback<Uri[]> filePathCallback;
    private static final int FILE_CHOOSER_REQUEST_CODE = 1;
    private static final int PERMISSION_ALL = 1;

    private DeviceConnection selectedPrinter = null;
    private int printerDpi = 203;
    private float printerWidthMm = 48f;
    private int printerNbrCharactersPerLine = 32;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Initialize views
        webView = findViewById(R.id.webview);
        progressBar = findViewById(R.id.progressBar);

        // Configure WebView settings
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setDatabaseEnabled(true);
        webSettings.setCacheMode(WebSettings.LOAD_DEFAULT);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setBuiltInZoomControls(false);
        webSettings.setSupportZoom(true);
        webSettings.setLoadWithOverviewMode(true);
        webSettings.setUseWideViewPort(true);
        webSettings.setUserAgentString(webSettings.getUserAgentString() + " DimsumSarangPOS/1.0.6");

        // Add JavaScript interface for better back button handling
        webView.addJavascriptInterface(new WebAppInterface(), "AndroidInterface");

        // Set WebViewClient
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                // Keep navigation within the app for ds.keetech.my.id
                if (url.contains("ds.keetech.my.id")) {
                    return false;
                }
                // Open external links in browser
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                startActivity(intent);
                return true;
            }

            @Override
            public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
                super.onPageStarted(view, url, favicon);
                progressBar.setVisibility(View.VISIBLE);
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                progressBar.setVisibility(View.GONE);

                // Inject JavaScript to handle back button properly
                webView.evaluateJavascript(
                        "(function() {" +
                // Set flag that this is Android app
                                "window.isAndroidApp = true;" +

                // Create global back button handler
                                "window.handleAndroidBackButton = function() {" +
                                "try {" +
                // Check for open modals first
                                "var modals = document.querySelectorAll('.modal.show, .modal-backdrop, .overlay.active, .popup.active, .v-overlay--active');"
                                +
                                "if (modals.length > 0) {" +
                                "console.log('Closing modal/overlay');" +
                                "modals.forEach(function(modal) {" +
                                "if (modal.classList.contains('modal')) {" +
                                "var closeBtn = modal.querySelector('.btn-close, .close, [data-dismiss=\"modal\"], .modal-close');"
                                +
                                "if (closeBtn) closeBtn.click();" +
                                "} else {" +
                                "modal.style.display = 'none';" +
                                "modal.classList.remove('show', 'active', 'v-overlay--active');" +
                                "}" +
                                "});" +
                                "return true;" +
                                "}" +

                // Check for Vue router (Vue 2/3)
                                "if (typeof window.$router !== 'undefined' && window.$router.go) {" +
                                "console.log('Using Vue router back');" +
                                "window.$router.go(-1);" +
                                "return true;" +
                                "}" +
                                "if (typeof window.router !== 'undefined' && window.router.go) {" +
                                "console.log('Using router back');" +
                                "window.router.go(-1);" +
                                "return true;" +
                                "}" +

                // Check for custom app back handler
                                "if (typeof window.appGoBack === 'function') {" +
                                "console.log('Using custom app back');" +
                                "return window.appGoBack();" +
                                "}" +

                // Use browser history as fallback
                                "if (window.history.length > 1) {" +
                                "console.log('Using browser history back');" +
                                "window.history.back();" +
                                "return true;" +
                                "}" +

                                "return false;" +
                                "} catch(e) {" +
                                "console.error('Back button handler error:', e);" +
                                "return false;" +
                                "}" +
                                "};" +

                // Override default back button behavior if app provides one
                                "document.addEventListener('DOMContentLoaded', function() {" +
                                "console.log('Android WebView back button handler ready');" +
                                "});" +

                                "})()",
                        null);
            }

            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                super.onReceivedError(view, errorCode, description, failingUrl);
                Toast.makeText(MainActivity.this, "Error loading page: " + description, Toast.LENGTH_SHORT).show();
            }
        });

        // Set WebChromeClient for file upload support
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback,
                    FileChooserParams fileChooserParams) {
                MainActivity.this.filePathCallback = filePathCallback;

                Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
                intent.addCategory(Intent.CATEGORY_OPENABLE);
                intent.setType("*/*");

                startActivityForResult(Intent.createChooser(intent, "Select File"), FILE_CHOOSER_REQUEST_CODE);
                return true;
            }

            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                super.onProgressChanged(view, newProgress);
                progressBar.setProgress(newProgress);
            }
        });

        // Load the main URL
        webView.loadUrl("https://ds.keetech.my.id");
    }

    @Override
    @SuppressWarnings("deprecation")
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == FILE_CHOOSER_REQUEST_CODE) {
            if (filePathCallback != null) {
                Uri[] results = null;
                if (resultCode == Activity.RESULT_OK && data != null) {
                    String dataString = data.getDataString();
                    if (dataString != null) {
                        results = new Uri[] { Uri.parse(dataString) };
                    }
                }
                filePathCallback.onReceiveValue(results);
                filePathCallback = null;
            }
        }
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        // Handle back button
        if (keyCode == KeyEvent.KEYCODE_BACK) {
            // Use the injected JavaScript handler
            webView.evaluateJavascript(
                    "window.handleAndroidBackButton()",
                    new ValueCallback<String>() {
                        @Override
                        public void onReceiveValue(String result) {
                            boolean handled = Boolean.parseBoolean(result);
                            if (!handled) {
                                // If JavaScript couldn't handle it, try WebView history
                                if (webView.canGoBack()) {
                                    webView.goBack();
                                } else {
                                    // Show exit dialog only if we're truly at the root
                                    showExitConfirmationDialog();
                                }
                            }
                        }
                    });
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }

    private void showExitConfirmationDialog() {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        builder.setTitle("Keluar Aplikasi");
        builder.setMessage("Apakah Anda yakin ingin keluar dari aplikasi?");
        builder.setIcon(android.R.drawable.ic_dialog_alert);

        // Tombol "Ya" untuk keluar
        builder.setPositiveButton("Ya", new DialogInterface.OnClickListener() {
            @Override
            public void onClick(DialogInterface dialog, int which) {
                finish();
                System.exit(0);
            }
        });

        // Tombol "Tidak" untuk membatalkan
        builder.setNegativeButton("Tidak", new DialogInterface.OnClickListener() {
            @Override
            public void onClick(DialogInterface dialog, int which) {
                dialog.dismiss();
            }
        });

        AlertDialog dialog = builder.create();
        dialog.show();
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        super.onPause();
        webView.onPause();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (webView != null) {
            webView.destroy();
        }
    }

    // JavaScript Interface for better communication with web app
    public class WebAppInterface {
        @android.webkit.JavascriptInterface
        public void exitApp() {
            finish();
            System.exit(0);
        }

        @android.webkit.JavascriptInterface
        public void showExitDialog() {
            runOnUiThread(new Runnable() {
                @Override
                public void run() {
                    showExitConfirmationDialog();
                }
            });
        }

        @android.webkit.JavascriptInterface
        public boolean canGoBack() {
            return webView.canGoBack();
        }

        @android.webkit.JavascriptInterface
        public String scanPrinters() {
            JSONArray printerList = new JSONArray();
            try {
                // Bluetooth Printers
                if (checkBluetoothPermissions()) {
                    BluetoothConnection[] bluetoothDevices = (new BluetoothPrintersConnections()).getList();
                    if (bluetoothDevices != null) {
                        for (BluetoothConnection device : bluetoothDevices) {
                            JSONObject obj = new JSONObject();
                            obj.put("name", device.getDevice().getName());
                            obj.put("address", device.getDevice().getAddress());
                            obj.put("type", "bluetooth");
                            printerList.put(obj);
                        }
                    }
                }

                // USB Printers
                UsbConnection[] usbDevices = (new UsbPrintersConnections(MainActivity.this)).getList();
                if (usbDevices != null) {
                    for (UsbConnection device : usbDevices) {
                        JSONObject obj = new JSONObject();
                        obj.put("name", "USB Printer");
                        obj.put("address", "usb");
                        obj.put("type", "usb");
                        printerList.put(obj);
                    }
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
            return printerList.toString();
        }

        @android.webkit.JavascriptInterface
        public void setPrinterSettings(int dpi, float widthMm, int charPerLine) {
            printerDpi = dpi;
            printerWidthMm = widthMm;
            printerNbrCharactersPerLine = charPerLine;
        }

        @android.webkit.JavascriptInterface
        public String connectBluetoothPrinter(String address) {
            try {
                if (!checkBluetoothPermissions()) {
                    return "ERROR: Permission denied";
                }
                BluetoothConnection[] bluetoothDevices = (new BluetoothPrintersConnections()).getList();
                if (bluetoothDevices != null) {
                    for (BluetoothConnection device : bluetoothDevices) {
                        if (device.getDevice().getAddress().equals(address)) {
                            selectedPrinter = device;
                            return "SUCCESS";
                        }
                    }
                }
                return "ERROR: Device not found";
            } catch (Exception e) {
                return "ERROR: " + e.getMessage();
            }
        }

        @android.webkit.JavascriptInterface
        public String connectUsbPrinter() {
            try {
                UsbConnection[] usbDevices = (new UsbPrintersConnections(MainActivity.this)).getList();
                if (usbDevices != null && usbDevices.length > 0) {
                    selectedPrinter = usbDevices[0];
                    return "SUCCESS";
                }
                return "ERROR: No USB printer found";
            } catch (Exception e) {
                return "ERROR: " + e.getMessage();
            }
        }

        @android.webkit.JavascriptInterface
        public String printFormattedText(String text) {
            if (selectedPrinter == null) {
                return "ERROR: No printer connected";
            }
            try {
                EscPosPrinter printer = new EscPosPrinter(selectedPrinter, printerDpi, printerWidthMm,
                        printerNbrCharactersPerLine);
                printer.printFormattedTextAndCut(text);
                return "SUCCESS";
            } catch (Exception e) {
                e.printStackTrace();
                return "ERROR: " + e.getMessage();
            }
        }

        @android.webkit.JavascriptInterface
        public String disconnectPrinter() {
            selectedPrinter = null;
            return "SUCCESS";
        }

        private boolean checkBluetoothPermissions() {
            if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.S) {
                if (ContextCompat.checkSelfPermission(MainActivity.this,
                        Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED ||
                        ContextCompat.checkSelfPermission(MainActivity.this,
                                Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED) {
                    ActivityCompat.requestPermissions(MainActivity.this,
                            new String[] { Manifest.permission.BLUETOOTH_CONNECT, Manifest.permission.BLUETOOTH_SCAN },
                            PERMISSION_ALL);
                    return false;
                }
            } else {
                if (ContextCompat.checkSelfPermission(MainActivity.this,
                        Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
                    ActivityCompat.requestPermissions(MainActivity.this,
                            new String[] { Manifest.permission.ACCESS_FINE_LOCATION }, PERMISSION_ALL);
                    return false;
                }
            }
            return true;
        }
    }
}