# バックエンド構成

> 💡 **マイクロサービス分割ではありません。**  
> 認可サーバー API での JWT 発行／検証のバックエンドを、**複数の言語・フレームワーク**で構成しています。  
> 同一の認可サーバー API に対する実装を、言語・フレームワーク別にディレクトリごとに分けています。

📖 **API仕様:** [Swagger UI](https://bobtabo.github.io/authorization/)

---

## 📂 実装スタック一覧

<!-- ロゴは各公式（Go は go.dev ブランド PNG 等）／ devicons（jsDelivr）。ドキュメント列は各ディレクトリの README.md。 -->

<table>
<thead>
<tr>
<th align="center"></th>
<th>スタック</th>
<th>ディレクトリ</th>
<th>ドキュメント</th>
<th align="center">状態</th>
</tr>
</thead>
<tbody>
<tr>
<td align="center"><img src="https://go.dev/blog/go-brand/Go-Logo/PNG/Go-Logo_Blue.png" width="45" height="40" alt="Go"></td>
<td><a href="https://go.dev/"><b>Go</b></a> + <a href="https://gin-gonic.com/"><b>Gin</b></a></td>
<td><a href="./go-gin/"><code>go-gin/</code></a></td>
<td><a href="./go-gin/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/java/java-original.svg" width="32" height="32" alt="Kotlin"></td>
<td><a href="https://www.java.com/"><b>Java</b></a> + <a href="https://spring.io/projects/spring-boot"><b>Spring Boot</b></a></td>
<td><a href="./java-springboot/"><code>java-springboot/</code></a></td>
<td><a href="./java-springboot/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" width="32" height="32" alt="PHP"></td>
<td><a href="https://www.php.net/"><b>PHP</b></a> + <a href="https://cakephp.org/jp"><b>CakePHP</b></a></td>
<td><a href="./php-cakephp/"><code>php-cakephp/</code></a></td>
<td><a href="./php-cakephp/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" width="32" height="32" alt="PHP"></td>
<td><a href="https://www.php.net/"><b>PHP</b></a> + <a href="https://codeigniter.com/"><b>Codeigniter</b></a></td>
<td><a href="./php-codeigniter/"><code>php-codeigniter/</code></a></td>
<td><a href="./php-codeigniter/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" width="32" height="32" alt="PHP"></td>
<td><a href="https://www.php.net/releases/7_4_0.php"><b>PHP 7.4</b></a> + <a href="https://fuelphp.com/"><b>FuelPHP</b></a></td>
<td><a href="php-fuelphp/"><code>php-fuelphp/</code></a></td>
<td><a href="php-fuelphp/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-original.svg" width="32" height="32" alt="Python"></td>
<td><a href="https://www.python.org/"><b>Python</b></a> + <a href="https://www.djangoproject.com/"><b>Django</b></a></td>
<td><a href="./python-django/"><code>python-django/</code></a></td>
<td><a href="./python-django/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
<tr>
<td align="center"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/ruby/ruby-original.svg" width="32" height="32" alt="Ruby"></td>
<td><a href="https://www.ruby-lang.org/"><b>Ruby</b></a> + <a href="https://rubyonrails.org/"><b>Ruby on Rails</b></a></td>
<td><a href="./ruby-rails/"><code>ruby-rails/</code></a></td>
<td><a href="./ruby-rails/README.md">README.md</a></td>
<td align="center">✅ 完了</td>
</tr>
</tbody>
</table>

### 凡例

| 記号 | 意味 |
|:---:|:---|
| ✅ | 完了 |
| 🚧 | 予定（未着手）／開発中 |

---

## 🔗 クイックリンク

| 絵文字 | リンク |
|:---:|:---|
| 🏠 | [リポジトリルート README](../README.md) |

各バックエンドの **起動・テスト・環境変数** は、上の表のディレクトリ内 README を参照してください。
