import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

URL = "http://localhost:3000/login.php"

# База пользователей с паролями
USERS_DB = {
    "admin": "admin123",
    "seva": "seva123",
    "lesha": "lesha123",
    "vova": "vova123",
    "iluha": "iluha123",
    "superadmin": "superadmin123"
}

def bypass_captcha(driver):
    driver.execute_script("""
        var hiddenInput = document.getElementById('captcha_sequence');
        if (hiddenInput) hiddenInput.value = '1,2,3,4';
    """)
    time.sleep(0.5)

def main():
    login_to_try = input("Введите логин: ").strip()
    
    print("\nЗапускаем Firefox...")
    driver = webdriver.Firefox()
    driver.maximize_window()
    
    try:
        # === СЦЕНАРИЙ БЛОКИРОВКИ ===
        if login_to_try == "zabanmenya":
            print(f"\nПользователь 'zabanmenya' выбран. Начинаем блокировку (5 неверных попыток)...")
            
            for attempt in range(1, 6):
                print(f"  Попытка {attempt} из 5...")
                driver.get(URL)
                WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.NAME, "login")))
                time.sleep(0.3)
                
                driver.find_element(By.NAME, "login").send_keys("zabanmenya")
                # Специально вводим неверный пароль
                driver.find_element(By.NAME, "password").send_keys(f"WRONG_{attempt}")
                bypass_captcha(driver)
                
                driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
                time.sleep(1.5)
                
                try:
                    error_msg = driver.find_element(By.CLASS_NAME, "error-message").text
                    print(f"    Сервер: {error_msg}")
                except:
                    print("    (Сервер не выдал ошибку)")
            
            print("\n✅ Готово. Пользователь 'zabanmenya' отправлен в бан.")
            return # Выходим, чтобы не выполнять код ниже

        # === СЦЕНАРИЙ ОБЫЧНОГО ВХОДА ===
        if login_to_try not in USERS_DB:
            print(f"❌ Ошибка: Пользователь '{login_to_try}' не найден в словаре скрипта.")
            return
            
        password_to_try = USERS_DB[login_to_try]
        print(f"\nВходим как '{login_to_try}'...")
        
        driver.get(URL)
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.NAME, "login")))
        time.sleep(0.5)
        
        driver.find_element(By.NAME, "login").send_keys(login_to_try)
        driver.find_element(By.NAME, "password").send_keys(password_to_try)
        bypass_captcha(driver)
        
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        time.sleep(2)
        
        if "profile.php" in driver.current_url:
            print(f"✅ УСПЕХ! Вы вошли как '{login_to_try}'.")
            print("-" * 30)
            print(driver.find_element(By.TAG_NAME, "body").text[:300] + "...")
            print("-" * 30)
        else:
            print(f"❌ НЕУДАЧА. Не удалось войти под '{login_to_try}'.")
            try:
                error = driver.find_element(By.CLASS_NAME, "error-message").text
                print(f"Причина: {error}")
            except:
                pass

    except Exception as e:
        print(f"\n💥 Ошибка: {e}")
    finally:
        input("\nНажми Enter, чтобы закрыть браузер...")
        driver.quit()

if __name__ == "__main__":
    main()