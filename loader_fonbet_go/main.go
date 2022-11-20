package main

import (
	"github.com/urfave/cli/v2"
	"loader_fonbet_go/commands"
	"log"
	"os"
)

func init() {
	cli.AppHelpTemplate = `NAME:
   {{.Name}} - {{.Usage}}
USAGE:
   {{.HelpName}} {{if .VisibleFlags}}[global options]{{end}}{{if .Commands}} command [command options]{{end}} {{if .ArgsUsage}}{{.ArgsUsage}}{{else}}[arguments...]{{end}}
   {{if len .Authors}}
AUTHOR:
   {{range .Authors}}{{ . }}{{end}}
   {{end}}{{if .Commands}}
КОМАНДЫ:
{{range .Commands}}{{if not .HideHelp}}   {{join .Names ", "}}{{ "\t"}}{{.Usage}}{{ "\n" }}{{end}}{{end}}{{end}}{{if .VisibleFlags}}
GLOBAL OPTIONS:
   {{range .VisibleFlags}}{{.}}
   {{end}}{{end}}{{if .Copyright }}
VERSION:
   {{.Version}}
   {{end}}
`
}

func main() {

	app := &cli.App{
		Name:    "loader",
		Usage:   "url loader",
		Version: "1.0.0",
		Commands: []*cli.Command{
			{
				Name:  "loadEvents",
				Usage: "Загрузка данных live",
				Flags: []cli.Flag{
					&cli.StringFlag{Name: "httpHeaders", Usage: "json объект заголовков", Required: true},
					&cli.StringFlag{Name: "httpTimeout", Usage: "Устанавливает таймаут соединения –httpTimeout 2"},
					&cli.StringFlag{Name: "countAttemptsLoad", Usage: "Количество попыток загрузки одного url"},
					&cli.StringFlag{Name: "templateUrlEvent", Usage: "Шаблон ссылки для загрузки события https://line03.by0e87-resources.by/events/event?lang=ru&eventId=[[eventId]]&scopeMarket=700&version=0", Required: true},
					&cli.StringFlag{Name: "countHTTPWorkers", Usage: "Количество потоков для загрузки если 0 или отрицательное число то каждый урл будет загружаться отдельно."},
					&cli.StringFlag{Name: "isUseHttpProxy", Usage: "Количество потоков для загрузки если 0 или отрицательное число то каждый урл будет загружаться отдельно."},
					&cli.StringFlag{Name: "proxyList", Usage: "json список проксей [\"...\",...]"},
					&cli.StringFlag{Name: "redisHostEnvName", Usage: "Название переменной окружения в которой лежит хост. Пример значения: 'REDIS_HOST'", Required: true},
					&cli.StringFlag{Name: "redisPortEnvName", Usage: "Название переменной окружения в которой лежит порт. Пример значения: 'REDIS_PORT'", Required: true},
					&cli.StringFlag{Name: "redisSelectDb", Usage: "База данных с которой будет работать загрузчик", Required: true},
					&cli.StringFlag{Name: "redisPrefix", Usage: "Префикс для ключей в редис."},
					&cli.StringFlag{Name: "redisPrefixForEvent", Usage: "Префикс для ключа события, которое будет загружать загрузчик.", Required: true},
					&cli.StringFlag{Name: "redisEventKeyTTL", Usage: "Время жизни для события.", Required: true},
					&cli.StringFlag{Name: "redisEventIdsChannel", Usage: "Имя (pub/sub) канала для идентификаторов событий. формат значений: [112,232,3232]", Required: true},
					&cli.StringFlag{Name: "redisProxyChannel", Usage: "Имя (pub/sub) канала для прокси. формат значений: [\"...\",\"...\",\"...\"]"},
					&cli.StringFlag{Name: "redisKeyForFailedProxies", Usage: "Ключ в редисе для нерабочих прокси"},
					&cli.StringFlag{Name: "updateEventDuration", Usage: "Время в миллисекундах 1с = 1000 мс. Время через которое нужно обновлять событие"},
					&cli.StringFlag{Name: "maxConnections", Usage: "Максимальное количество TCP соединений по умолчанию: --maxConnections 1000"},
				},
				Action: func(cCtx *cli.Context) error {
					return commands.NewLoadEvents(
						cCtx.String("httpHeaders"),
						cCtx.Int("httpTimeout"),
						cCtx.Int("countAttemptsLoad"),
						cCtx.String("templateUrlEvent"),
						cCtx.Int("countHTTPWorkers"),
						cCtx.Bool("isUseHttpProxy"),
						cCtx.String("proxyList"),
						cCtx.String("redisHostEnvName"),
						cCtx.String("redisPortEnvName"),
						cCtx.Int("redisSelectDb"),
						cCtx.String("redisPrefix"),
						cCtx.String("redisPrefixForEvent"),
						cCtx.Int("redisEventKeyTTL"),
						cCtx.String("redisEventIdsChannel"),
						cCtx.String("redisProxyChannel"),
						cCtx.String("redisKeyForFailedProxies"),
						cCtx.Int("updateEventDuration"),
						cCtx.Int("maxConnections"),
					).Run()
				},
			},
		},
	}
	if err := app.Run(os.Args); err != nil {
		log.Fatal(err)
	}
}
