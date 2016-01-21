/* Catalog parser for http://www.tribuna.com.ru */
package main

import ( // {{{
	"encoding/json"
	//	"sort"
	"sync"
	//	"regexp"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	"net/http"
	"strconv"
	"strings"
	"time"
) // }}}

/*
Product structure
*/
type Product struct { // {{{
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
	//	Desc []string `json:"desc"`
} // }}}

/*
Products array
*/
type Products []Product

var ( // {{{
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	categories        = make(map[string]string)
	err               error
	counter           int
	failed            []string
	wg                sync.WaitGroup
) // }}}

const ( // {{{
	baseURL    = "http://www.tribuna.com.ru"
	outputDir  = "images"
	outputJSON = "tribuna.json"
) // }}}

func checkErr(msg string, err error) { // {{{
	if err != nil {
		fmt.Printf("%s - %s\n", msg, err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

/* func translit(s string) string {// {{{
	return unidecode.Unidecode(strings.Replace(s, " ", "_", -1))
}

func downloadFromURL(url string, outputDir string) error {
	fileName := getFilename(url)
	output, err := os.Create(outputDir + string(filepath.Separator) + fileName)
	if err != nil {
		log.Println("Ошибка при создании", outputDir+string(filepath.Separator)+fileName, "-", err)
		return err
	}
	defer output.Close()

	response, err := http.Get(url)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}
	defer response.Body.Close()

	n, err := io.Copy(output, response.Body)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}

	//	fmt.Println(n, "байтов загружено.")
	if n == 0 {
		return errors.New("Null size image")
	} else {
		return nil
	}
} */ // }}}

func contains(slice []string, item string) bool { // {{{
	set := make(map[string]struct{}, len(slice))
	for _, s := range slice {
		set[s] = struct{}{}
	}

	_, ok := set[item]
	return ok
} // }}}

func getPageDoc(url string) *goquery.Document {
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	checkErr("ERROR in getPageDoc::NewRequest", err)
	req.Header.Add("Accept-Encoding", "identity")
	req.Close = true

	response, err := client.Do(req)
	checkErr("ERROR in getPageDoc::Do", err)

	defer response.Body.Close()

	doc, err := goquery.NewDocumentFromResponse(response)
	checkErr("ERROR in getPageDoc::NewDocumentFromResponse", err)
	return doc
}

func getCategories() map[string]string { // {{{
	/* excludeList := []string{
		"Прошлые коллекции",
	} */
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	categories["Классическая коллекция - Бюстгальтер"] = "http://www.tribuna.com.ru/products/?filter2=9&filter=1"
	categories["Классическая коллекция - Трусы"] = "http://www.tribuna.com.ru/products/?filter2=11474&filter=1"
	categories["Классическая коллекция - Панталоны"] = "http://www.tribuna.com.ru/products/?filter2=7&filter=1"
	categories["Модная коллекция - Бюстгальтер"] = "http://www.tribuna.com.ru/products/?filter2=9&filter=2"
	categories["Модная коллекция - Трусы"] = "http://www.tribuna.com.ru/products/?filter2=11474&filter=2"
	categories["Купальники - Купальники слитные"] = "http://www.tribuna.com.ru/products/?filter2=12&filter=3"
	categories["Купальники - Купальники раздельные"] = "http://www.tribuna.com.ru/products/?filter2=20&filter=3"
	categories["Купальники - Блузки-туники"] = "http://www.tribuna.com.ru/products/?filter2=22&filter=3"
	categories["Купальники - Парео"] = "http://www.tribuna.com.ru/products/?filter2=16&filter=3"

	/* doc, _ := goquery.NewDocument(baseURL)
	doc.Find("#topblock > div.secondmenu > div:nth-child(1) > div:nth-child(1) > div.col-xs-12.col-sm-6").Each(func(i int, s *goquery.Selection) {
		catLink, _ := s.Find("a").Attr("href")
		cat := s.Find("a").Text()
		catLink = strings.TrimSpace(catLink)
		if !contains(excludeList, cat) {
			//			fmt.Printf("%s => %s\n", cat, strings.TrimSpace(catLink))
			subDoc, _ := goquery.NewDocument(baseURL + catLink)
			time.Sleep(25 * time.Second)
			fmt.Printf("Curr subCat link => '%s'\n", baseURL+catLink)
			subDoc.Find("div.main > div.container > div.row > div.col-xs-12.col-md-9 > div.fast-filter > div.one-filter").Each(func(j int, ss *goquery.Selection) {
				subCat := ss.Find("a").Text()
				subCatLink, _ := ss.Find("a").Attr("href")
				fmt.Printf("** %s - %s => %s\n", cat, subCat, baseURL+"/products/"+subCatLink)
				categories[cat+" - "+subCat] = baseURL + "/products/" + subCatLink
			})
		}
	}) */
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div.g-title-wrap > div.pages > a:not(.next)").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	//	fmt.Printf("Найдено страниц - %d\n", last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		//		last := getPages(catLink)
		//		for i := 1; i <= last; i++ {
		//		currPage := fmt.Sprintf("%s%s%s", catLink, "?PAGEN_1=", strconv.Itoa(i))
		//			fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
		//		doc, _ = goquery.NewDocument(currPage)
		getProducts(getPageDoc(catLink), cat)
		//		}
	}
	wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	//	ret, _ := doc.Html()
	//	fmt.Printf("getProducts doc - %s\n", ret)
	doc.Find("#catalog > div > div").Each(func(i int, s *goquery.Selection) {
		link, _ := s.Find("div.modelbox > div.action > a").Attr("href")
		link = strings.Split(link, "	")[0]
		pict, _ := s.Find("div.modelbox > div.action > img").Attr("src")
		// fmt.Printf("*** Получена ссылка для %s - %s\n", cat, strings.Replace(link, "	", "", -1))
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		go getProductInfo(baseURL+link, baseURL+pict, cat)
		wg.Add(1)
	})
} // }}}

func getProductInfo(url string, pict string, cat string) { // {{{
	defer wg.Done()
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp := getPageDoc(url)

	p := Product{}
	tmpSKU := docp.Find("body > div.main > div > div:nth-child(2) > div > div.col-xs-12.col-md-7 > div.name").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		return
	}
	p.Sku = strings.Split(strings.TrimSpace(tmpSKU), " ")[0]
	p.Name = strings.TrimLeft(tmpSKU, p.Sku+" ")
	p.Pict = pict
	p.Link = url
	p.Category = cat

	desc := make(map[string]string)
	docp.Find("body > div.main > div > div:nth-child(2) > div > div.col-xs-12.col-md-7 > div.tab-text-block > div.tabs > a").Each(func(i int, sa *goquery.Selection) {
		title := strings.TrimSpace(sa.Text())
		if title != "Возврат" {
			rel, _ := sa.Attr("rel")
			desc[title] = strings.TrimSpace(docp.Find("div.tabs-content > div" + rel).Text())
			//		fmt.Printf("%s => %s\n", title, desc[title])
		}
	})
	p.Desc = desc

	//	fmt.Printf("%+v\n", p)

	products = append(products, p)
	counter++
	fmt.Print(".")
} // }}}

func main() { // {{{
	ts := time.Now()
	/*
		if _, err := os.Stat(outputDir); os.IsNotExist(err) {
			os.MkdirAll(outputDir, 0777)
		}
	*/
	fmt.Println("* Начинаем сбор страниц")
	categories = getCategories()

	walkinPages()
	//	doc, _ := goquery.NewDocument(baseURL + "?page=all")
	//	getProducts(doc)

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	fmt.Printf("Не обработано - %d\n", len(failed))
	for _, fail := range failed {
		fmt.Printf("FAIL => %s\n", fail)
	}
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
} // }}}
