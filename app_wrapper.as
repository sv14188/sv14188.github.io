import sys
from PyQt5.QtCore import QUrl
from PyQt5.QtWidgets import QApplication, QMainWindow
from PyQt5.QtWebEngineWidgets import QWebEngineView

class WebAppWindow(QMainWindow):
    def __init__(self, url):
        super().__init__()
        self.setWindowTitle("Uw Webshop App")
        self.setGeometry(100, 100, 1200, 800) # Stel startgrootte in

        # De webbrowser view
        self.browser = QWebEngineView()
        self.browser.setUrl(QUrl(url))
        
        # Plaats de browser in het hoofdvenster
        self.setCentralWidget(self.browser)
        self.show()

if __name__ == '__main__':
    app = QApplication(sys.argv)
    
    # Gebruik de URL van uw lokale of live webshop
    webshop_url = 'http://localhost/Webshop-project/checkout.php' # Of https://uwdomein.nl
    
    window = WebAppWindow(webshop_url)
    sys.exit(app.exec_())