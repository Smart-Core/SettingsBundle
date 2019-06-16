Settings Bundle
===============

Installation
------------
 
1) Необходимо прописать в Kernel.php следующий код:
    ```php
    
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass)
    {
        parent::dumpContainer($cache, $container, $class, $baseClass);
    
        /** @var ContainerInterface $container2 */
        $container = require $cache->getPath();
        $container->set('kernel', $this);
        $container->get('settings')->warmupDatabase();
    }
    ```

2) Затем в бандле по марштруту /Resources/config/settings.yml описать конфиг настроек в следующем формате:

    ```yaml
    # Short format
    option1: value 1
    
    # Full specs
    option2:
        type: TextType # CheckboxType, ChoiceType, CheckboxType etc...
        hidden: true # Скрывать в админке
        value: |
            Многострочный
            Текст.
        title: Если указан заголовок, то будет отображаться вместо имени.
        description: Подробное описание настройки
        validation:
            - NotBlank: ~
            - Range:
                min: 120
                max: 180
        choices:
            ru: Россия
            de: Германия
    
    
        # @todo
        group: main 
        update_callback: \My\Setting\Callback::option2 # Вызов стататического метода либо сервиса при обновлении параметра.
    ```

3) Чтобы файл settings.yml прочитался, необходимо создать DependencyInjection\*Extension для того, чтобы бандл получил в системе Extension alias.

TODO
----
 - Конфигурирование приложения Symfony 4, например через /config/packages/smart_settings.yaml  
 - Решить как поступать в случае если настройка была сохранена в БД, но потом удалена из конфига. Здесь можно либо удалять настройку из бд, либо помечать is_active = false.
